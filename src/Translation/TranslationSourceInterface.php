<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Translation;

use Gingerminds\MultisiteBundle\Exception\TranslationSourceException;

/**
 * Downloads the front translations xlsx of a site (Google Drive by default:
 * GoogleDriveTranslationSource). Replace the `gingerminds_multisite.translation.source`
 * service to read it from elsewhere.
 */
interface TranslationSourceInterface
{
    /**
     * @param array<mixed> $credentials decoded service account JSON
     *
     * @return string absolute path of a temporary xlsx file, deleted by the caller
     *
     * @throws TranslationSourceException
     */
    public function download(string $fileId, array $credentials): string;
}
