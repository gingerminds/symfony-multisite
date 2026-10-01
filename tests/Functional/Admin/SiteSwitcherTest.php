<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional\Admin;

use Gingerminds\MultisiteBundle\Controller\Site\SiteSwitchController;
use Gingerminds\MultisiteBundle\Tests\Functional\ApiTestCase;

final class SiteSwitcherTest extends ApiTestCase
{
    public function testHiddenWithASingleSite(): void
    {
        $this->fixtures->site('only');
        $this->client->loginUser($this->fixtures->user('single@example.com', superAdmin: true), 'admin');

        $this->client->request('GET', '/admin/');

        self::assertSelectorNotExists('.gm-site-switcher');
    }

    public function testSwitchesTheAdminSite(): void
    {
        $first = $this->fixtures->site('alpha');
        $second = $this->fixtures->site('beta');
        $this->client->loginUser($this->fixtures->user('switch@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', '/admin/sites');
        self::assertSelectorTextContains('#gm-sidebar .gm-site-switcher .dropdown-toggle', 'alpha');
        // Bottom of the sidebar, right above the user menu.
        $sidebar = $crawler->filter('#gm-sidebar')->html();
        self::assertGreaterThan(strpos($sidebar, 'id="gm-sidebar-menu"'), strpos($sidebar, 'gm-site-switcher'));
        self::assertLessThan(strrpos($sidebar, 'sidebar-profile-toggle'), strpos($sidebar, 'gm-site-switcher'));
        self::assertSame((string) $first->getId(), $crawler->filter('.gm-site-switcher .dropdown-item.active')->attr('value'));

        $form = $crawler->filter('.gm-site-switcher button[value="' . $second->getId() . '"]')->form();
        $this->client->submit($form, [], ['HTTP_REFERER' => 'http://localhost/admin/sites?page=1']);

        self::assertResponseRedirects('/admin/sites?page=1');
        self::assertSame($second->getId(), $this->client->getRequest()->getSession()->get(SiteSwitchController::SESSION_KEY));
        $this->client->followRedirect();
        self::assertSelectorTextContains('#gm-sidebar .gm-site-switcher .dropdown-toggle', 'beta');
    }

    public function testRejectsAnInvalidTokenAndExternalReferers(): void
    {
        $this->fixtures->site('alpha');
        $second = $this->fixtures->site('beta');
        $this->client->loginUser($this->fixtures->user('attacker@example.com', superAdmin: true), 'admin');

        $this->client->request('POST', '/admin/site-switch', ['_token' => 'invalid', 'site_id' => $second->getId()], server: ['HTTP_REFERER' => 'https://evil.example.com/phishing']);

        self::assertResponseRedirects('/admin/');
        self::assertNull($this->client->getRequest()->getSession()->get(SiteSwitchController::SESSION_KEY));
    }
}
