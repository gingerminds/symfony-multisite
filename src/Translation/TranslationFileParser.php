<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Translation;

use Gingerminds\MultisiteBundle\Exception\TranslationSourceException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parses the front translations xlsx: a header row with a "key" column then one
 * column per locale (key, fr, en...), then one row per translation key.
 */
final class TranslationFileParser
{
    /**
     * @return array<string, array<string, string>> locale => [key => value]
     *
     * @throws TranslationSourceException
     */
    public function parse(string $path): array
    {
        /** @var list<list<mixed>> $rows */
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);

        if ([] === $rows) {
            return [];
        }

        $header = array_map(static fn (mixed $value): ?string => \is_string($value) ? mb_strtolower(trim($value)) : null, array_shift($rows));
        $keyColumn = array_search('key', $header, true);

        if (false === $keyColumn) {
            throw TranslationSourceException::missingKeyColumn();
        }

        $locales = [];

        foreach ($header as $column => $locale) {
            if ($column !== $keyColumn && null !== $locale && '' !== $locale) {
                $locales[$locale] = $column;
            }
        }

        $translations = array_fill_keys(array_keys($locales), []);

        foreach ($rows as $row) {
            $key = $row[$keyColumn] ?? null;

            if (!\is_string($key) || '' === trim($key)) {
                continue;
            }

            foreach ($locales as $locale => $column) {
                $value = $row[$column] ?? null;
                $translations[$locale][trim($key)] = \is_scalar($value) ? (string) $value : '';
            }
        }

        return $translations;
    }
}
