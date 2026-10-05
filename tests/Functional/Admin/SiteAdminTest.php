<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\Site;
use Gingerminds\MultisiteBundle\Security\CredentialsEncryptor;
use Gingerminds\MultisiteBundle\Tests\Functional\ApiTestCase;

final class SiteAdminTest extends ApiTestCase
{
    private const string CREDENTIALS = '{"type": "service_account", "client_email": "bot@example.iam.gserviceaccount.com", "private_key": "secret"}';

    public function testListAndPermissions(): void
    {
        $fr = $this->fixtures->language('fr', 'Français');
        $this->fixtures->site('corporate', [$fr], $fr);
        $this->client->loginUser($this->fixtures->user('viewer@example.com', ['view sites']), 'admin');

        $this->client->request('GET', '/admin/sites');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#gm-list-table', 'corporate');
        self::assertSelectorTextContains('#gm-list-table', 'Français');

        $this->client->request('GET', '/admin/sites/new');
        self::assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/admin/languages');
        self::assertResponseStatusCodeSame(403);
    }

    public function testMenuEntriesAreInTheAdministrationSection(): void
    {
        $this->client->loginUser($this->fixtures->user('menu@example.com', ['view sites', 'view users']), 'admin');

        $crawler = $this->client->request('GET', '/admin/');
        $section = $crawler->filter('#gm-sidebar-menu > li')->reduce(
            static fn ($item): bool => 1 === $item->filter('a[href="/admin/users"]')->count(),
        );

        self::assertCount(1, $section, 'Users and sites share the Administration section.');
        self::assertCount(1, $section->filter('a[href="/admin/sites"]'));
        self::assertSame(['/admin/users', '/admin/sites'], $section->filter('a')->each(static fn ($link): ?string => $link->attr('href')));
        self::assertCount(0, $crawler->filter('#gm-sidebar a[href="/admin/languages"]'));
    }

    public function testMenuAdministrationSectionWithOnlyMultisiteEntries(): void
    {
        $this->client->loginUser($this->fixtures->user('sites-only@example.com', ['view sites']), 'admin');

        $crawler = $this->client->request('GET', '/admin/');

        self::assertCount(1, $crawler->filter('#gm-sidebar a[href="/admin/sites"]'));
        self::assertCount(0, $crawler->filter('#gm-sidebar a[href="/admin/users"]'));
    }

    public function testCreateSite(): void
    {
        $fr = $this->fixtures->language('fr');
        $en = $this->fixtures->language('en');
        $this->client->loginUser($this->fixtures->user('creator@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', '/admin/sites/new');
        $form = $crawler->filter('form[name="site"]')->form();
        $form['site[code]'] = 'brand';
        $form['site[url]'] = 'https://brand.example.com';
        $form['site[front_urls]'] = "https://www.brand.example.com\n\n https://shop.brand.example.com \nhttps://www.brand.example.com";
        $form['site[languages]']->select([(string) $fr->getId(), (string) $en->getId()]);
        $form['site[default_language]'] = (string) $en->getId();
        $form['site[google_drive_file_id]'] = 'drive-file';
        $form['site[google_service_account_credentials]'] = self::CREDENTIALS;
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/sites');

        $this->entityManager()->clear();
        $site = $this->site('brand');
        self::assertSame(['fr', 'en'], array_map(static fn (LanguageInterface $language): ?string => $language->getIso(), $site->getLanguages()));
        self::assertSame('en', $site->getDefaultLanguage()?->getIso());
        self::assertSame(['https://shop.brand.example.com', 'https://www.brand.example.com'], $site->getFrontUrlValues());
        self::assertSame('drive-file', $site->getGoogleDriveFileId());
        self::assertStringNotContainsString('secret', (string) $site->getEncryptedGoogleCredentials());
        self::assertSame(
            json_decode(self::CREDENTIALS, true),
            self::getContainer()->get(CredentialsEncryptor::class)->decrypt((string) $site->getEncryptedGoogleCredentials()),
        );
    }

    public function testEditKeepsTheCredentialsWhenLeftBlankAndSyncsTheRelations(): void
    {
        $fr = $this->fixtures->language('fr');
        $en = $this->fixtures->language('en');
        $site = $this->fixtures->site('brand', [$fr, $en], $fr);
        $site->setFrontUrlValues(['https://old.example.com', 'https://kept.example.com']);
        $site->setEncryptedGoogleCredentials('v1:stored');
        $this->entityManager()->flush();
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', '/admin/sites/' . $site->getId() . '/edit');
        self::assertSelectorNotExists('textarea#site_google_service_account_credentials:not(:empty)');
        $form = $crawler->filter('form[name="site"]')->form();
        $form['site[front_urls]'] = "https://kept.example.com\nhttps://new.example.com";
        $form['site[languages]']->select([(string) $en->getId()]);
        $form['site[default_language]'] = (string) $en->getId();
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/sites/' . $site->getId() . '/edit');

        $this->entityManager()->clear();
        $site = $this->site('brand');
        self::assertSame(['en'], array_map(static fn (LanguageInterface $language): ?string => $language->getIso(), $site->getLanguages()));
        self::assertSame('en', $site->getDefaultLanguage()?->getIso());
        self::assertSame(['https://kept.example.com', 'https://new.example.com'], $site->getFrontUrlValues());
        self::assertSame('v1:stored', $site->getEncryptedGoogleCredentials());
    }

    public function testInvalidSubmissionsAreRejected(): void
    {
        $fr = $this->fixtures->language('fr');
        $en = $this->fixtures->language('en');
        $this->fixtures->site('taken');
        $this->client->loginUser($this->fixtures->user('invalid@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', '/admin/sites/new');
        $form = $crawler->filter('form[name="site"]')->form();
        $form['site[code]'] = 'taken';
        $form['site[url]'] = 'not an url';
        $form['site[front_urls]'] = 'not an url either';
        $form['site[languages]']->select([(string) $fr->getId()]);
        $form['site[default_language]'] = (string) $en->getId();
        $form['site[google_service_account_credentials]'] = '{invalid json';
        $crawler = $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        foreach (['code', 'url', 'front_urls', 'default_language', 'google_service_account_credentials'] as $field) {
            self::assertCount(1, $crawler->filter('#site_' . $field . '.is-invalid'), $field . ' is invalid');
        }
        self::assertNull($this->entityManager()->getRepository(Site::class)->findOneBy(['url' => 'not an url']));
    }

    public function testDeleteSite(): void
    {
        $site = $this->fixtures->site('obsolete', [$this->fixtures->language('fr')]);
        $site->setFrontUrlValues(['https://obsolete.example.com']);
        $this->entityManager()->flush();
        $id = $site->getId();
        $this->client->loginUser($this->fixtures->user('deleter@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', '/admin/sites');
        $token = $crawler->filter('[data-gm-delete-url="/admin/sites/' . $id . '/delete"]')->attr('data-gm-delete-token');
        $this->client->request('POST', '/admin/sites/' . $id . '/delete', ['_token' => $token]);

        self::assertResponseRedirects('/admin/sites');
        $this->entityManager()->clear();
        self::assertNull($this->entityManager()->find(Site::class, $id));
    }

    private function site(string $code): Site
    {
        $site = $this->entityManager()->getRepository(Site::class)->findOneBy(['code' => $code]);
        self::assertInstanceOf(Site::class, $site);

        return $site;
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
