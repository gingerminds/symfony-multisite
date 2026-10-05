<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional\Context;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\MultisiteBundle\Controller\Site\SiteSwitchController;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use Gingerminds\MultisiteBundle\Entity\Site\Site;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Article;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\ArticleTranslation;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Media;
use Gingerminds\MultisiteBundle\Tests\Functional\ApiTestCase;

/**
 * What a front request sees (tests/Application ContentController): current site
 * and language resolution, site and language Doctrine filters, current
 * translations with fallback.
 */
final class ContextTest extends ApiTestCase
{
    private Site $alpha;
    private Site $beta;

    protected function setUp(): void
    {
        parent::setUp();

        $fr = $this->fixtures->language('fr');
        $en = $this->fixtures->language('en');
        $this->fixtures->language('de');
        $this->alpha = $this->fixtures->site('alpha', [$fr, $en], $fr, 'https://alpha.example.com');
        $this->beta = $this->fixtures->site('beta', [$en], $en, 'https://beta.example.com');

        $this->article('alpha-news', $this->alpha, ['fr' => [$fr, 'Actualité'], 'en' => [$en, 'News']]);
        $this->article('beta-news', $this->beta, ['en' => [$en, 'Beta news']]);
        $this->article('shared', null, ['fr' => [$fr, 'Partagé']]);

        $this->media('everywhere-fr', [$fr]);
        $this->media('everywhere-en', [$en]);
        $this->media('no-language', []);
    }

    public function testHostAndAcceptLanguage(): void
    {
        $content = $this->content('alpha.example.com', 'en-GB,fr;q=0.8');

        self::assertSame('alpha', $content['site']);
        self::assertSame(['en', 'fr'], [$content['language'], $content['fallback']]);
        // Site filter: the site articles and the shared ones; translations: current, else fallback.
        self::assertSame([
            ['code' => 'alpha-news', 'title' => 'News', 'switch_lang' => ['fr' => 'alpha-news-fr', 'en' => 'alpha-news-en']],
            ['code' => 'shared', 'title' => 'Partagé', 'switch_lang' => ['fr' => 'shared-fr']],
        ], $content['articles']);
        // Language filter: the medias attached to the current language only.
        self::assertSame(['everywhere-en'], $content['medias']);
    }

    public function testLanguageNotEnabledOnTheSiteFallsBackToTheDefaultOne(): void
    {
        $content = $this->content('alpha.example.com', 'de');

        self::assertSame(['fr', 'fr'], [$content['language'], $content['fallback']]);
        self::assertSame(['Actualité', 'Partagé'], array_column($content['articles'], 'title'));
        self::assertSame(['everywhere-fr'], $content['medias']);
    }

    public function testSiteHeaderWinsOverTheHost(): void
    {
        foreach ([(string) $this->beta->getId(), 'beta'] as $header) {
            $content = $this->content('alpha.example.com', null, $header);

            self::assertSame('beta', $content['site'], 'X-Site-Id: ' . $header);
            self::assertSame('en', $content['language']);
            // No title in `en` nor in the fallback (`en`) for the shared article.
            self::assertSame(['beta-news' => 'Beta news', 'shared' => null], array_column($content['articles'], 'title', 'code'));
        }
    }

    public function testUnknownHostFallsBackToTheFirstSite(): void
    {
        self::assertSame('alpha', $this->content('unknown.example.com')['site']);
    }

    public function testAdminSiteSwitcherChoiceWinsInTheSession(): void
    {
        $this->client->loginUser($this->fixtures->user('switch@example.com', superAdmin: true), 'admin');
        $crawler = $this->client->request('GET', '/admin/');
        $this->client->submit($crawler->filter('.gm-site-switcher button[value="' . $this->beta->getId() . '"]')->form());

        self::assertSame($this->beta->getId(), $this->client->getRequest()->getSession()->get(SiteSwitchController::SESSION_KEY));
        self::assertSame('beta', $this->content('alpha.example.com')['site']);
    }

    public function testNoSiteNoSiteContextedRow(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM ' . ArticleTranslation::class)->execute();
        $entityManager->createQuery('DELETE FROM ' . Article::class)->execute();
        $entityManager->createQuery('DELETE FROM ' . Site::class)->execute();
        $this->article('orphan', null, []);

        $content = $this->content('alpha.example.com');

        self::assertNull($content['site']);
        self::assertSame([], $content['articles'], 'Like the Laravel global scope: nothing without current site.');
        self::assertCount(3, $content['medias'], 'No language filter without current language.');
    }

    /**
     * @return array{site: string|null, language: string|null, fallback: string|null, articles: list<array{code: string, title: string|null, switch_lang: array<string, string>}>, medias: list<string>}
     */
    private function content(string $host, ?string $acceptLanguage = null, ?string $siteHeader = null): array
    {
        $server = ['HTTP_HOST' => $host];

        if (null !== $acceptLanguage) {
            $server['HTTP_ACCEPT_LANGUAGE'] = $acceptLanguage;
        }

        if (null !== $siteHeader) {
            $server['HTTP_X_SITE_ID'] = $siteHeader;
        }

        $this->client->request('GET', '/content', server: $server);
        self::assertResponseIsSuccessful();

        /** @var array{site: string|null, language: string|null, fallback: string|null, articles: list<array{code: string, title: string|null, switch_lang: array<string, string>}>, medias: list<string>} */
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, array{Language, string}> $translations
     */
    private function article(string $code, ?Site $site, array $translations): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $article = new Article($code);
        $article->setSite($site);

        foreach ($translations as $iso => [$language, $title]) {
            $translation = new ArticleTranslation();
            $translation->setLanguage($language);
            $translation->setTitle($title);
            $translation->setSlug($code . '-' . $iso);
            $article->addTranslation($translation);
        }

        $entityManager->persist($article);
        $entityManager->flush();
    }

    /**
     * @param list<Language> $languages
     */
    private function media(string $name, array $languages): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $media = new Media($name);

        foreach ($languages as $language) {
            $media->addLanguage($language);
        }

        $entityManager->persist($media);
        $entityManager->flush();
    }
}
