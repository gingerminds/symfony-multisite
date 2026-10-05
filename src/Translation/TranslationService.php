<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Translation;

use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Security\CredentialsEncryptor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Front translations of a site, read "live" from its Google Drive xlsx with a
 * cache protecting the Google API quota (`translation.cache_ttl`, 0: until the
 * next refresh). A download or parsing error is logged (never the credentials
 * nor the file) and gives no translation, cached for one minute only.
 */
class TranslationService
{
    public const int ERROR_TTL = 60;

    public function __construct(
        private readonly TranslationSourceInterface $source,
        private readonly TranslationFileParser $parser,
        private readonly CredentialsEncryptor $encryptor,
        private readonly CacheInterface $cache,
        private readonly bool $enabled,
        private readonly int $cacheTtl,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isEnabledForSite(SiteInterface $site): bool
    {
        return $this->enabled && null !== $site->getGoogleDriveFileId() && $site->hasGoogleCredentials();
    }

    /**
     * @return array<string, array<string, string>> locale => [key => value]
     */
    public function getTranslationsForSite(SiteInterface $site): array
    {
        if (!$this->isEnabledForSite($site)) {
            return [];
        }

        return $this->cache->get($this->cacheKey($site), function (ItemInterface $item) use ($site): array {
            $translations = $this->fetch($site);
            $item->expiresAfter($this->cacheLifetime(null !== $translations));

            return $translations ?? [];
        });
    }

    /**
     * @return array<string, string> key => value
     */
    public function getTranslationsForSiteAndLocale(SiteInterface $site, string $locale): array
    {
        return $this->getTranslationsForSite($site)[mb_strtolower($locale)] ?? [];
    }

    public function resetCacheForSite(SiteInterface $site): void
    {
        $this->cache->delete($this->cacheKey($site));
    }

    /**
     * Seconds the fetched translations stay cached: `cache_ttl` (null: until the next
     * refresh when 0), one minute only after an error.
     */
    private function cacheLifetime(bool $fetched): ?int
    {
        if (!$fetched) {
            return self::ERROR_TTL;
        }

        return 0 === $this->cacheTtl ? null : $this->cacheTtl;
    }

    /**
     * The cache key only depends on the site: the refresh runs outside of any request.
     */
    private function cacheKey(SiteInterface $site): string
    {
        return 'gingerminds_multisite.translations.site_' . $site->getId();
    }

    /**
     * @return array<string, array<string, string>>|null null on error
     */
    private function fetch(SiteInterface $site): ?array
    {
        $path = null;

        try {
            $credentials = $this->encryptor->decrypt((string) $site->getEncryptedGoogleCredentials());
            $path = $this->source->download((string) $site->getGoogleDriveFileId(), $credentials);

            return $this->parser->parse($path);
        } catch (\Throwable $exception) {
            $this->logger->warning('Unable to fetch the front translations from Google Drive.', [
                'site_id' => $site->getId(),
                'message' => $exception->getMessage(),
            ]);

            return null;
        } finally {
            if (null !== $path && is_file($path)) {
                unlink($path);
            }
        }
    }
}
