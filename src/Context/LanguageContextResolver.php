<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Context;

use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The languages of a request on a site: the current one is the Accept-Language preferred one
 * (quality values included) enabled on the site, the fallback is the site default language
 * (its first language when none is flagged); the current one falls back to it.
 */
class LanguageContextResolver
{
    /**
     * @return array{0: LanguageInterface|null, 1: LanguageInterface|null} current, fallback
     */
    public function resolve(SiteInterface $site, ?Request $request): array
    {
        $languages = $site->getLanguages();
        $fallback = $site->getDefaultLanguage() ?? $languages[0] ?? null;

        if (!$fallback instanceof LanguageInterface || !$request instanceof Request) {
            return [$fallback, $fallback];
        }

        // The fallback first: the answer when no language of the site is accepted.
        $candidates = [$fallback];

        foreach ($languages as $language) {
            if ($language !== $fallback) {
                $candidates[] = $language;
            }
        }

        $preferred = self::preferred($request, array_map(static fn (LanguageInterface $language): string => (string) $language->getIso(), $candidates));

        foreach ($candidates as $language) {
            if ($language->getIso() === $preferred) {
                return [$language, $fallback];
            }
        }

        return [$fallback, $fallback];
    }

    /**
     * The code of `$codes` (ISO codes, `pt-br`) the request prefers (Request::getPreferredLanguage()),
     * the first one when it accepts none.
     *
     * @param list<string> $codes
     */
    public static function preferred(Request $request, array $codes): ?string
    {
        $locale = $request->getPreferredLanguage($codes);

        foreach ($codes as $code) {
            if (null !== $locale && self::locale($code) === mb_strtolower($locale)) {
                return $code;
            }
        }

        return $codes[0] ?? null;
    }

    /**
     * The Symfony locale of an ISO code: `pt-br` → `pt_br` (compared case-insensitively).
     */
    public static function locale(string $code): string
    {
        return mb_strtolower(str_replace('-', '_', $code));
    }
}
