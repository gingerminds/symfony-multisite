<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Application\Translation;

use Gingerminds\MultisiteBundle\Exception\TranslationSourceException;
use Gingerminds\MultisiteBundle\Translation\TranslationSourceInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Google Drive replacement of the test application: writes `$rows` to a real xlsx.
 * Static state: the test client reboots the kernel between requests.
 */
final class FakeTranslationSource implements TranslationSourceInterface
{
    /** @var list<list<string|int|null>> */
    public static array $rows = [];

    /** @var list<array{file: string, credentials: array<mixed>}> */
    public static array $downloads = [];

    public static bool $fail = false;

    public static function reset(): void
    {
        self::$rows = [];
        self::$downloads = [];
        self::$fail = false;
    }

    public function download(string $fileId, array $credentials): string
    {
        self::$downloads[] = ['file' => $fileId, 'credentials' => $credentials];

        if (self::$fail) {
            throw new TranslationSourceException('Google Drive is down.');
        }

        return self::write(self::$rows);
    }

    /**
     * @param list<list<string|int|null>> $rows
     */
    public static function write(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1', true);
        $path = sys_get_temp_dir() . '/gm-translations-' . bin2hex(random_bytes(4)) . '.xlsx';
        new Xlsx($spreadsheet)->save($path);

        return $path;
    }
}
