<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional\Translation;

use Gingerminds\MultisiteBundle\Entity\Site\Site;
use Gingerminds\MultisiteBundle\Security\CredentialsEncryptor;
use Gingerminds\MultisiteBundle\Tests\Application\Translation\FakeTranslationSource;
use Gingerminds\MultisiteBundle\Tests\Functional\ApiTestCase;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Front translations from the (fake) Google Drive xlsx: API, cache, admin refresh.
 */
final class TranslationApiTest extends ApiTestCase
{
    private const array CREDENTIALS = ['type' => 'service_account', 'client_email' => 'bot@example.com'];

    private Site $alpha;

    protected function setUp(): void
    {
        parent::setUp();

        FakeTranslationSource::reset();
        FakeTranslationSource::$rows = [
            ['key', 'fr', 'en'],
            ['home.title', 'Accueil', 'Home'],
            ['cart.empty', 'Panier vide', 'Empty cart'],
        ];
        $cache = self::getContainer()->get('gingerminds_multisite.translation_cache');
        self::assertInstanceOf(CacheInterface::class, $cache);
        $cache->clear();

        $this->alpha = $this->configuredSite('alpha', 'https://alpha.example.com');
    }

    public function testEveryLocaleOfTheCurrentSite(): void
    {
        // No Accept-Language (Request::create() sends `en-us,en;q=0.5` by default).
        $data = $this->translations(['HTTP_ACCEPT_LANGUAGE' => '']);

        $this->assertStatus(200);
        self::assertSame([
            ['locale' => 'fr', 'values' => ['home.title' => 'Accueil', 'cart.empty' => 'Panier vide']],
            ['locale' => 'en', 'values' => ['home.title' => 'Home', 'cart.empty' => 'Empty cart']],
        ], array_map(static fn (array $item): array => ['locale' => $item['locale'], 'values' => $item['values']], $data['member']));
        self::assertSame([['file' => 'drive-alpha', 'credentials' => self::CREDENTIALS]], FakeTranslationSource::$downloads, 'Decrypted credentials.');
    }

    public function testOnlyTheAcceptLanguageLocale(): void
    {
        self::assertSame(['en'], array_column($this->translations(['HTTP_ACCEPT_LANGUAGE' => 'en-GB,fr;q=0.5'])['member'], 'locale'));
        self::assertSame([], $this->translations(['HTTP_ACCEPT_LANGUAGE' => 'de'])['member']);
    }

    public function testSiteQueryParameter(): void
    {
        $this->configuredSite('beta', 'https://beta.example.com');

        $this->translations(query: '?site=beta');

        self::assertSame('drive-beta', FakeTranslationSource::$downloads[0]['file']);
    }

    public function testCachedBetweenRequests(): void
    {
        $this->translations();
        $this->translations();

        self::assertCount(1, FakeTranslationSource::$downloads);
    }

    public function testSiteWithoutDriveConfigurationHasNoTranslation(): void
    {
        $this->fixtures->site('bare', url: 'https://bare.example.com');

        self::assertSame([], $this->translations(host: 'bare.example.com')['member']);
        self::assertSame([], FakeTranslationSource::$downloads);
    }

    public function testADriveErrorGivesNoTranslationAndIsNotRetriedRightAway(): void
    {
        FakeTranslationSource::$fail = true;

        self::assertSame([], $this->translations()['member']);
        $this->assertStatus(200);
        $this->translations();

        self::assertCount(1, FakeTranslationSource::$downloads);
    }

    public function testAdminRefreshReDownloadsTheFile(): void
    {
        $this->translations();
        $this->client->loginUser($this->fixtures->user('translator@example.com', ['view sites', 'edit sites', 'manage translations']), 'admin');

        $crawler = $this->client->request('GET', '/admin/sites/' . $this->alpha->getId() . '/edit');
        // The button lives in the Translations card of the site form, its form after it.
        $button = $crawler->filter('form[name="site"] button[form="gm-translations-refresh"]');
        self::assertCount(1, $button);
        self::assertCount(0, $crawler->filter('.page-title-right form'));
        $this->client->submit($crawler->filter('form#gm-translations-refresh')->form());

        self::assertResponseRedirects('/admin/sites/' . $this->alpha->getId() . '/edit');
        self::assertCount(2, FakeTranslationSource::$downloads, 'Cache reset and warmed up again (Messenger, synchronous here).');
        $this->client->followRedirect();
        self::assertSelectorExists('.alert-success');
    }

    public function testAdminRefreshRequiresThePermissionAndAValidToken(): void
    {
        $this->client->loginUser($this->fixtures->user('editor@example.com', ['view sites', 'edit sites']), 'admin');

        $this->client->request('GET', '/admin/sites/' . $this->alpha->getId() . '/edit');
        self::assertSelectorNotExists('form#gm-translations-refresh');
        self::assertSelectorNotExists('button[form="gm-translations-refresh"]');

        $this->client->request('POST', '/admin/translations/refresh', ['site_id' => $this->alpha->getId()]);
        self::assertResponseStatusCodeSame(403);

        $this->client->loginUser($this->fixtures->user('translator@example.com', ['view sites', 'manage translations']), 'admin');
        $edit = 'http://localhost/admin/sites/' . $this->alpha->getId() . '/edit';
        $this->client->request('POST', '/admin/translations/refresh', ['site_id' => $this->alpha->getId(), '_token' => 'invalid'], server: ['HTTP_REFERER' => $edit]);
        self::assertResponseRedirects('/admin/sites/' . $this->alpha->getId() . '/edit');
        self::assertSame([], FakeTranslationSource::$downloads);
    }

    /**
     * @param array<string, string> $server
     *
     * @return array{member: list<array{locale: string, values: array<string, string>}>}
     */
    private function translations(array $server = [], string $query = '', string $host = 'alpha.example.com'): array
    {
        $this->client->request('GET', '/api/translations' . $query, server: ['HTTP_ACCEPT' => 'application/ld+json', 'HTTP_HOST' => $host, ...$server]);

        /** @var array{member: list<array{locale: string, values: array<string, string>}>} */
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    private function configuredSite(string $code, string $url): Site
    {
        $site = $this->fixtures->site($code, url: $url);
        $site->setGoogleDriveFileId('drive-' . $code);
        $site->setEncryptedGoogleCredentials(self::getContainer()->get(CredentialsEncryptor::class)->encrypt(self::CREDENTIALS));
        self::getContainer()->get('doctrine')->getManager()->flush();

        return $site;
    }
}
