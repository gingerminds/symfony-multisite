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

        $header = $this->header(array_shift($rows));
        $keyColumn = array_search('key', $header, true);

        if (false === $keyColumn) {
            throw TranslationSourceException::missingKeyColumn();
        }

        return $this->translations($rows, $keyColumn, $this->localeColumns($header, $keyColumn));
    }

    /**
     * @param list<mixed> $row
     *
     * @return array<int, string|null> lowercase trimmed labels, null for a non text cell
     */
    private function header(array $row): array
    {
        return array_map(static fn (mixed $value): ?string => \is_string($value) ? mb_strtolower(trim($value)) : null, $row);
    }

    /**
     * @param array<int, string|null> $header
     *
     * @return array<string, int> locale => column
     */
    private function localeColumns(array $header, int $keyColumn): array
    {
        $locales = [];

        foreach ($header as $column => $locale) {
            if ($column !== $keyColumn && null !== $locale && '' !== $locale) {
                $locales[$locale] = $column;
            }
        }

        return $locales;
    }

    /**
     * @param list<list<mixed>>  $rows
     * @param array<string, int> $locales locale => column
     *
     * @return array<string, array<string, string>> locale => [key => value]
     */
    private function translations(array $rows, int $keyColumn, array $locales): array
    {
        $translations = array_fill_keys(array_keys($locales), []);

        foreach ($rows as $row) {
            $key = $row[$keyColumn] ?? null;

            if (!\is_string($key) || '' === trim($key)) {
                continue;
            }

            foreach ($locales as $locale => $column) {
                $translations[$locale][trim($key)] = $this->cell($row[$column] ?? null);
            }
        }

        return $translations;
    }

    private function cell(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : '';
    }
}
