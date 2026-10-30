<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Translation;

use Gingerminds\MultisiteBundle\Exception\TranslationSourceException;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Psr\Http\Message\ResponseInterface;

/**
 * Downloads the translations spreadsheet from Google Drive with the site service
 * account: a native Google Sheet is exported to xlsx, an uploaded xlsx is
 * downloaded as is. Files on a shared drive are supported.
 */
final class GoogleDriveTranslationSource implements TranslationSourceInterface
{
    private const string GOOGLE_SHEET_MIME_TYPE = 'application/vnd.google-apps.spreadsheet';
    private const string XLSX_MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function download(string $fileId, array $credentials): string
    {
        $client = new GoogleClient();
        $client->setAuthConfig($credentials);
        $client->addScope(GoogleDrive::DRIVE_READONLY);

        $drive = new GoogleDrive($client);
        $mimeType = (string) $drive->files->get($fileId, ['fields' => 'mimeType', 'supportsAllDrives' => true])->getMimeType();

        /** @var ResponseInterface $response */
        $response = self::GOOGLE_SHEET_MIME_TYPE === $mimeType
            ? $drive->files->export($fileId, self::XLSX_MIME_TYPE, ['alt' => 'media'])
            : $drive->files->get($fileId, ['alt' => 'media', 'supportsAllDrives' => true]);

        $path = tempnam(sys_get_temp_dir(), 'gm_translation_');

        if (false === $path) {
            throw TranslationSourceException::temporaryFile();
        }

        file_put_contents($path, $response->getBody()->getContents());

        return $path;
    }
}
