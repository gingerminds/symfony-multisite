<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Security;

final readonly class CredentialsEncryptor
{
    private const string PREFIX = 'v1:';

    public function __construct(
        #[\SensitiveParameter]
        private string $secret,
    ) {
    }

    /**
     * @param array<mixed> $credentials decoded service account JSON
     */
    public function encrypt(array $credentials): string
    {
        $nonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox(json_encode($credentials, \JSON_THROW_ON_ERROR), $nonce, $this->key());

        return self::PREFIX . base64_encode($nonce . $cipher);
    }

    /**
     * @return array<mixed>
     *
     * @throws \RuntimeException when the value cannot be decrypted (other key, corrupted value)
     */
    public function decrypt(string $encrypted): array
    {
        $raw = str_starts_with($encrypted, self::PREFIX) ? base64_decode(substr($encrypted, \strlen(self::PREFIX)), true) : false;

        if (false === $raw || \strlen($raw) <= \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \RuntimeException('Invalid encrypted credentials.');
        }

        $plain = sodium_crypto_secretbox_open(
            substr($raw, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($raw, 0, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            $this->key(),
        );

        if (false === $plain) {
            throw new \RuntimeException('The credentials cannot be decrypted with the configured encryption key.');
        }

        $credentials = json_decode($plain, true, flags: \JSON_THROW_ON_ERROR);

        return \is_array($credentials) ? $credentials : [];
    }

    /**
     * @throws \LogicException when the encryption key is empty (e.g. an unset APP_SECRET)
     */
    private function key(): string
    {
        if ('' === trim($this->secret)) {
            throw new \LogicException('The credentials encryption key is empty: set APP_SECRET (or gingerminds_multisite.translation.encryption_key).');
        }

        return sodium_crypto_generichash($this->secret, '', \SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
