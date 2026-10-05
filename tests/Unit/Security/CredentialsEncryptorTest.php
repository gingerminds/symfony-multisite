<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Unit\Security;

use Gingerminds\MultisiteBundle\Exception\CredentialsEncryptionException;
use Gingerminds\MultisiteBundle\Security\CredentialsEncryptor;
use PHPUnit\Framework\TestCase;

final class CredentialsEncryptorTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $encryptor = new CredentialsEncryptor('app-secret');
        $credentials = ['type' => 'service_account', 'private_key' => "-----BEGIN PRIVATE KEY-----\nabc"];

        $encrypted = $encryptor->encrypt($credentials);

        self::assertStringStartsWith('v1:', $encrypted);
        self::assertStringNotContainsString('PRIVATE KEY', $encrypted);
        self::assertNotSame($encrypted, $encryptor->encrypt($credentials), 'A random nonce per encryption.');
        self::assertSame($credentials, $encryptor->decrypt($encrypted));
    }

    public function testAnotherKeyCannotDecrypt(): void
    {
        $encrypted = new CredentialsEncryptor('app-secret')->encrypt(['a' => 1]);

        $this->expectException(CredentialsEncryptionException::class);
        new CredentialsEncryptor('rotated-secret')->decrypt($encrypted);
    }

    public function testRefusesAnEmptyKey(): void
    {
        $this->expectException(CredentialsEncryptionException::class);
        new CredentialsEncryptor(' ')->encrypt(['a' => 1]);
    }

    public function testRejectsAnUnknownFormat(): void
    {
        $this->expectException(CredentialsEncryptionException::class);
        new CredentialsEncryptor('app-secret')->decrypt('{"plain": "json"}');
    }
}
