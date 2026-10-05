<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Exception;

/**
 * The front translations file cannot be downloaded from Google Drive or parsed
 * (missing "key" column, unreadable file...). TranslationService catches and
 * logs it: the API then answers with no translation instead of failing.
 */
final class TranslationSourceException extends \RuntimeException
{
    public static function missingKeyColumn(): self
    {
        return new self('The translation xlsx file must have a "key" header column.');
    }

    public static function temporaryFile(): self
    {
        return new self('Unable to create a temporary file to download the translation xlsx.');
    }
}
