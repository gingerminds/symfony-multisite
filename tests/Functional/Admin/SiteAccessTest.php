<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\MultisiteBundle\Controller\Site\SiteSwitchController;
use Gingerminds\MultisiteBundle\Entity\Site\Site;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Security\SiteAccess;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\User;
use Gingerminds\MultisiteBundle\Tests\Functional\ApiTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

final class SiteAccessTest extends ApiTestCase
{
    private Site $alpha;
    private Site $beta;
    private Site $gamma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->alpha = $this->fixtures->site('alpha');
        $this->beta = $this->fixtures->site('beta');
        $this->gamma = $this->fixtures->site('gamma');
    }

    public function testTheAdminOnlyShowsTheSitesOfTheUser(): void
    {
        $this->client->loginUser($this->fixtures->user('limited@example.com', ['view sites'], sites: [$this->beta, $this->gamma]), 'admin');

        $crawler = $this->client->request('GET', '/admin/sites');

        self::assertResponseIsSuccessful();
        self::assertSame(['beta', 'gamma'], $crawler->filter('.gm-site-switcher .dropdown-item')->each(static fn ($item): string => trim($item->text())));
        self::assertSelectorTextContains('.gm-site-switcher .dropdown-toggle', 'beta', 'The first allowed site, not the first site.');
        self::assertSelectorTextNotContains('#gm-list-table', 'alpha');

        $this->client->request('GET', '/admin/sites/' . $this->alpha->getId() . '/edit');
        self::assertResponseStatusCodeSame(404);
    }

    public function testTheSwitcherRejectsASiteOfAnotherUser(): void
    {
        $this->client->loginUser($this->fixtures->user('switcher@example.com', sites: [$this->beta, $this->gamma]), 'admin');
        $crawler = $this->client->request('GET', '/admin/');
        $token = (string) $crawler->filter('.gm-site-switcher input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/site-switch', ['_token' => $token, 'site_id' => $this->alpha->getId()]);

        self::assertNull($this->client->getRequest()->getSession()->get(SiteSwitchController::SESSION_KEY));
    }

    public function testTheApiDeniesASiteOfAnotherUser(): void
    {
        $token = $this->fixtures->token($this->fixtures->user('api-limited@example.com', sites: [$this->beta]));

        $this->client->request('GET', '/api/sites', server: ['HTTP_ACCEPT' => 'application/ld+json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'HTTP_X_SITE_ID' => 'alpha']);
        $this->assertStatus(403);

        $data = $this->api('GET', '/api/sites', $token);
        $this->assertStatus(200);
        self::assertSame(['beta'], array_column($data['member'], 'code'), 'The sites list of a restricted user.');
    }

    public function testAUserLeftWithoutSiteIsDenied(): void
    {
        $this->client->loginUser($this->fixtures->user('nowhere@example.com', sites: []), 'admin');

        $this->client->request('GET', '/admin/');

        self::assertResponseStatusCodeSame(403);
    }

    public function testASuperAdminAndAUserWithAllSitesAreNotRestricted(): void
    {
        $access = self::getContainer()->get(SiteAccess::class);

        self::assertNull($access->allowedSiteIds($this->fixtures->user('root@example.com', superAdmin: true, sites: [$this->beta])));
        self::assertNull($access->allowedSiteIds($this->fixtures->user('everywhere@example.com')));
        self::assertSame([$this->beta->getId()], $access->allowedSiteIds($this->fixtures->user('beta@example.com', sites: [$this->beta])));
    }

    public function testTheUsersListFiltersBySite(): void
    {
        $this->fixtures->user('on-beta@example.com', sites: [$this->beta]);
        $this->fixtures->user('on-gamma@example.com', sites: [$this->gamma]);
        $this->client->loginUser($this->fixtures->user('list-root@example.com', superAdmin: true), 'admin');

        $this->client->request('GET', '/admin/users?sites=' . $this->beta->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#gm-list-table', 'on-beta@example.com');
        self::assertSelectorTextNotContains('#gm-list-table', 'on-gamma@example.com');
    }

    public function testTheUsersApiFiltersBySite(): void
    {
        $this->fixtures->user('api-beta@example.com', sites: [$this->beta]);
        $this->fixtures->user('api-gamma@example.com', sites: [$this->gamma]);
        $token = $this->fixtures->token($this->fixtures->user('api-root@example.com', superAdmin: true));

        $data = $this->api('GET', '/api/users?sites=' . $this->beta->getId(), $token);

        $this->assertStatus(200);
        self::assertSame(['api-beta@example.com'], array_column($data['member'], 'email'));
    }

    public function testASuperAdminAssignsAnySiteOrAllSites(): void
    {
        $this->client->loginUser($this->fixtures->user('root-form@example.com', superAdmin: true), 'admin');
        $user = $this->fixtures->user('target@example.com', sites: [$this->alpha]);

        $crawler = $this->client->request('GET', '/admin/users/' . $user->getId() . '/edit');
        self::assertSame(['alpha', 'beta', 'gamma'], $this->siteChoices($crawler));
        self::assertCount(1, $crawler->filter('input[name="user[all_sites]"]'));

        $form = $crawler->filter('form[name="user"]')->form();
        $this->tickSites($form, [$this->gamma]);
        $this->client->submit($form);
        self::assertResponseRedirects();

        self::assertSame(['gamma'], $this->siteCodes($this->reload($user)));
    }

    public function testALimitedAssignerOnlyGivesTheirSitesAndKeepsTheOthers(): void
    {
        $this->client->loginUser($this->fixtures->user('assigner@example.com', [SiteAccess::ASSIGN_PERMISSION, 'view users', 'edit users'], sites: [$this->beta]), 'admin');
        $user = $this->fixtures->user('kept@example.com', sites: [$this->alpha]);

        $crawler = $this->client->request('GET', '/admin/users/' . $user->getId() . '/edit');
        self::assertSame(['beta'], $this->siteChoices($crawler));
        self::assertCount(0, $crawler->filter('input[name="user[all_sites]"]'), 'A limited assigner never grants every site.');

        $form = $crawler->filter('form[name="user"]')->form();
        $this->tickSites($form, [$this->beta]);
        $this->client->submit($form);
        self::assertResponseRedirects();

        self::assertSame(['alpha', 'beta'], $this->siteCodes($this->reload($user)));
    }

    public function testALimitedAssignerDoesNotEditTheSitesOfAUserWithAllSites(): void
    {
        $this->client->loginUser($this->fixtures->user('assigner-all@example.com', [SiteAccess::ASSIGN_PERMISSION, 'view users', 'edit users'], sites: [$this->beta]), 'admin');
        $user = $this->fixtures->user('global@example.com');

        $this->client->request('GET', '/admin/users/' . $user->getId() . '/edit');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('input[name="user[sites][]"]');
    }

    public function testNobodyButASuperAdminEditsTheirOwnSites(): void
    {
        $assigner = $this->fixtures->user('self@example.com', [SiteAccess::ASSIGN_PERMISSION, 'view users', 'edit users']);
        $this->client->loginUser($assigner, 'admin');

        $this->client->request('GET', '/admin/users/' . $assigner->getId() . '/edit');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('input[name="user[sites][]"]');
    }

    public function testAUserCreatedWithoutTheAssignPermissionGetsTheSitesOfTheCreator(): void
    {
        $this->client->loginUser($this->fixtures->user('creator@example.com', ['view users', 'edit users'], sites: [$this->beta, $this->gamma]), 'admin');

        $crawler = $this->client->request('GET', '/admin/users/new');
        self::assertSelectorNotExists('input[name="user[sites][]"]');

        $form = $crawler->filter('form[name="user"]')->form();
        $form['user[email]'] = 'created@example.com';
        $form['user[password][first]'] = 'created-password';
        $form['user[password][second]'] = 'created-password';
        $this->client->submit($form);
        self::assertResponseRedirects();

        $created = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => 'created@example.com']);
        self::assertInstanceOf(User::class, $created);
        self::assertFalse($created->hasAllSites());
        self::assertSame(['beta', 'gamma'], $this->siteCodes($created));
    }

    /**
     * @param list<Site> $sites the ticked ones, the other ones unticked
     */
    private function tickSites(Form $form, array $sites): void
    {
        $ids = array_map(static fn (Site $site): string => (string) $site->getId(), $sites);

        foreach ($form['user[sites]'] as $checkbox) {
            \assert($checkbox instanceof ChoiceFormField);
            \in_array($checkbox->availableOptionValues()[0], $ids, true) ? $checkbox->tick() : $checkbox->untick();
        }
    }

    /**
     * @return list<string>
     */
    private function siteChoices(Crawler $crawler): array
    {
        return $crawler->filter('input[name="user[sites][]"]')->each(
            static fn ($input): string => trim($input->closest('.form-check')?->filter('label')->text() ?? ''),
        );
    }

    /**
     * @return list<string>
     */
    private function siteCodes(User $user): array
    {
        $codes = array_map(static fn (SiteInterface $site): string => (string) $site->getCode(), $user->getSites()->toArray());
        sort($codes);

        return $codes;
    }

    private function reload(User $user): User
    {
        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $reloaded);

        return $reloaded;
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
