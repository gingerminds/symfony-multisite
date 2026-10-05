<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Exception;

final class CredentialsEncryptionException extends \RuntimeException
{
    public static function invalidFormat(): self
    {
        return new self('Invalid encrypted credentials.');
    }

    public static function cannotDecrypt(): self
    {
        return new self('The credentials cannot be decrypted with the configured encryption key.');
    }

    public static function emptyKey(): self
    {
        return new self('The credentials encryption key is empty: set APP_SECRET (or gingerminds_multisite.translation.encryption_key).');
    }
}
