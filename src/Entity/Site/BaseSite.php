<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Site;

use ApiPlatform\Metadata\ApiProperty;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\CacheableResourceInterface;
use Gingerminds\CoreBundle\Model\EagerLoadableInterface;
use Gingerminds\CoreBundle\Model\SearchableInterface;
use Gingerminds\CoreBundle\Model\SortableInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
#[UniqueEntity(fields: ['code'])]
abstract class BaseSite implements SiteInterface, TimestampableInterface, SortableInterface, SearchableInterface, EagerLoadableInterface, CacheableResourceInterface, \Stringable
{
    use TimestampableTrait;

    public const string GROUP_LIST = 'site:list';
    public const string GROUP_READ = 'site:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: true)]
    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    protected ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    protected ?string $code = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Url(requireTld: false)]
    #[Assert\Length(max: 255)]
    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    protected ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    protected ?string $googleDriveFileId = null;

    /**
     * Never serialized nor redisplayed.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $googleServiceAccountCredentials = null;

    /**
     * @var Collection<int, SiteLanguage>
     */
    protected Collection $siteLanguages;

    /**
     * @var Collection<int, SiteFrontUrl>
     */
    protected Collection $frontUrls;

    public function __construct()
    {
        $this->siteLanguages = new ArrayCollection();
        $this->frontUrls = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getSiteLanguages(): Collection
    {
        return $this->siteLanguages;
    }

    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    #[SerializedName('languages')]
    public function getLanguages(): array
    {
        $languages = [];

        foreach ($this->siteLanguages as $siteLanguage) {
            $languages[] = $siteLanguage->getLanguage();
        }

        return $languages;
    }

    public function setLanguages(iterable $languages): void
    {
        $kept = [];

        foreach ($languages as $language) {
            $kept[] = $language;

            if (!$this->hasLanguage($language)) {
                $this->siteLanguages->add(new SiteLanguage($this, $language));
            }
        }

        foreach ($this->siteLanguages as $siteLanguage) {
            if (!array_any($kept, static fn (LanguageInterface $language): bool => $siteLanguage->isFor($language))) {
                $this->siteLanguages->removeElement($siteLanguage);
            }
        }
    }

    public function hasLanguage(LanguageInterface $language): bool
    {
        return $this->siteLanguages->exists(static fn (int $key, SiteLanguage $siteLanguage): bool => $siteLanguage->isFor($language));
    }

    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    #[SerializedName('default_language')]
    public function getDefaultLanguage(): ?LanguageInterface
    {
        foreach ($this->siteLanguages as $siteLanguage) {
            if ($siteLanguage->isDefault()) {
                return $siteLanguage->getLanguage();
            }
        }

        return null;
    }

    public function setDefaultLanguage(?LanguageInterface $language): void
    {
        foreach ($this->siteLanguages as $siteLanguage) {
            $siteLanguage->setDefault($language instanceof LanguageInterface && $siteLanguage->isFor($language));
        }
    }

    public function getFrontUrls(): Collection
    {
        return $this->frontUrls;
    }

    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    #[SerializedName('front_urls')]
    public function getFrontUrlValues(): array
    {
        $urls = [];

        foreach ($this->frontUrls as $frontUrl) {
            $urls[] = $frontUrl->getUrl();
        }

        return $urls;
    }

    public function setFrontUrlValues(iterable $urls): void
    {
        $values = [];

        foreach ($urls as $url) {
            $url = trim($url);

            if ('' !== $url) {
                $values[$url] = $url;
            }
        }

        foreach ($this->frontUrls as $frontUrl) {
            if (!isset($values[$frontUrl->getUrl()])) {
                $this->frontUrls->removeElement($frontUrl);
            }

            unset($values[$frontUrl->getUrl()]);
        }

        foreach ($values as $url) {
            $this->frontUrls->add(new SiteFrontUrl($this, $url));
        }
    }

    public function getGoogleDriveFileId(): ?string
    {
        return $this->googleDriveFileId;
    }

    public function setGoogleDriveFileId(?string $fileId): void
    {
        $this->googleDriveFileId = null === $fileId || '' === trim($fileId) ? null : trim($fileId);
    }

    public function getEncryptedGoogleCredentials(): ?string
    {
        return $this->googleServiceAccountCredentials;
    }

    public function setEncryptedGoogleCredentials(?string $credentials): void
    {
        $this->googleServiceAccountCredentials = $credentials;
    }

    public function hasGoogleCredentials(): bool
    {
        return null !== $this->googleServiceAccountCredentials && '' !== $this->googleServiceAccountCredentials;
    }

    public static function getSearchableFields(): array
    {
        return ['code', 'url'];
    }

    public static function getEagerLoads(): array
    {
        return ['siteLanguages', 'siteLanguages.language', 'frontUrls'];
    }

    public static function getCacheKey(): string
    {
        return 'site';
    }

    public static function getCacheTtl(): int
    {
        return 86400;
    }

    public function __toString(): string
    {
        return (string) $this->code;
    }
}
