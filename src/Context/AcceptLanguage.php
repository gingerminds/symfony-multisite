<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Context;

/**
 * Parses an Accept-Language header into primary language codes, in the header
 * order (as the Laravel package did, quality values are not used for sorting):
 * `fr-FR,en;q=0.8,fr` gives `['fr', 'en']`.
 */
final class AcceptLanguage
{
    /**
     * @return list<string> lowercase primary subtags, without duplicates
     */
    public static function parse(?string $header): array
    {
        if (null === $header || '' === trim($header)) {
            return [];
        }

        $locales = [];

        foreach (explode(',', $header) as $part) {
            $tag = trim(explode(';', $part)[0]);
            $primary = mb_strtolower(trim(explode('-', str_replace('_', '-', $tag))[0]));

            if ('' !== $primary && '*' !== $primary) {
                $locales[$primary] = $primary;
            }
        }

        return array_values($locales);
    }

    /**
     * The first requested primary language code, null when none.
     */
    public static function primary(?string $header): ?string
    {
        return self::parse($header)[0] ?? null;
    }
}
