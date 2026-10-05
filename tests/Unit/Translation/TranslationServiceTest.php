<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Unit\Translation;

use Gingerminds\MultisiteBundle\Entity\Site\Site;
use Gingerminds\MultisiteBundle\Security\CredentialsEncryptor;
use Gingerminds\MultisiteBundle\Tests\Application\Translation\FakeTranslationSource;
use Gingerminds\MultisiteBundle\Translation\TranslationFileParser;
use Gingerminds\MultisiteBundle\Translation\TranslationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class TranslationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        FakeTranslationSource::reset();
        FakeTranslationSource::$rows = [['key', 'fr'], ['hello', 'Bonjour']];
    }

    public function testTheMasterSwitchDisablesEverySite(): void
    {
        $site = $this->site();

        self::assertTrue($this->service(enabled: true)->isEnabledForSite($site));
        self::assertFalse($this->service(enabled: false)->isEnabledForSite($site));
        self::assertSame([], $this->service(enabled: false)->getTranslationsForSite($site));
        self::assertSame([], FakeTranslationSource::$downloads);
    }

    public function testTranslationsOfALocale(): void
    {
        $service = $this->service(enabled: true);

        self::assertSame(['hello' => 'Bonjour'], $service->getTranslationsForSiteAndLocale($this->site(), 'FR'));
        self::assertSame([], $service->getTranslationsForSiteAndLocale($this->site(), 'de'));
    }

    private function service(bool $enabled): TranslationService
    {
        return new TranslationService(new FakeTranslationSource(), new TranslationFileParser(), new CredentialsEncryptor('secret'), new ArrayAdapter(), $enabled, 300);
    }

    private function site(): Site
    {
        $site = new Site();
        $site->setGoogleDriveFileId('drive');
        $site->setEncryptedGoogleCredentials(new CredentialsEncryptor('secret')->encrypt(['type' => 'service_account']));

        return $site;
    }
}
