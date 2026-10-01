<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use Gingerminds\MultisiteBundle\Tests\Functional\ApiTestCase;

final class LanguageAdminTest extends ApiTestCase
{
    public function testCreateLanguageNormalizesTheIsoCode(): void
    {
        $this->client->loginUser($this->fixtures->user('creator@example.com', ['view languages', 'edit languages']), 'admin');

        $crawler = $this->client->request('GET', '/admin/languages/new');
        $this->client->submit($crawler->filter('form[name="language"]')->form([
            'language[iso]' => ' DE ',
            'language[label]' => 'Deutsch',
        ]));

        self::assertResponseRedirects('/admin/languages');
        $language = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Language::class)->findOneBy(['label' => 'Deutsch']);
        self::assertInstanceOf(Language::class, $language);
        self::assertSame('de', $language->getIso());

        $this->client->followRedirect();
        self::assertSelectorTextContains('#gm-list-table', 'Deutsch');
    }

    public function testIsoCodeIsUnique(): void
    {
        $this->fixtures->language('fr');
        $this->client->loginUser($this->fixtures->user('duplicate@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', '/admin/languages/new');
        $crawler = $this->client->submit($crawler->filter('form[name="language"]')->form([
            'language[iso]' => 'fr',
            'language[label]' => 'Français bis',
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertCount(1, $crawler->filter('#language_iso.is-invalid'));
    }

    public function testEditRequiresThePermission(): void
    {
        $language = $this->fixtures->language('fr');
        $this->client->loginUser($this->fixtures->user('reader@example.com', ['view languages']), 'admin');

        $this->client->request('GET', '/admin/languages/' . $language->getId() . '/edit');

        self::assertResponseStatusCodeSame(403);
    }
}
