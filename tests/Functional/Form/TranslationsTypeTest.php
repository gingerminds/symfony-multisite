<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional\Form;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Article;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\ArticleTranslation;
use Gingerminds\MultisiteBundle\Tests\Application\Form\ArticleType;
use Gingerminds\MultisiteBundle\Tests\Functional\ApiTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

final class TranslationsTypeTest extends ApiTestCase
{
    private Language $fr;
    private Language $en;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fr = $this->fixtures->language('fr');
        $this->en = $this->fixtures->language('en');
        $this->fixtures->language('de');
        self::getContainer()->get(SiteContext::class)->setSite($this->fixtures->site('alpha', [$this->fr, $this->en], $this->fr));
    }

    public function testOneEntryPerSiteLanguageTheDefaultOneRequired(): void
    {
        $translations = $this->form(new Article('new'))->get('translations');

        self::assertSame([$this->fr->getId(), $this->en->getId()], array_keys($translations->all()));
        self::assertTrue($translations->get((string) $this->fr->getId())->isRequired());
        self::assertFalse($translations->get((string) $this->en->getId())->isRequired());
    }

    public function testAnOptionalLanguageLeftEmptyIsNotCreated(): void
    {
        $article = new Article('new');
        $form = $this->submit($article, ['fr' => ['title' => 'Actualité', 'slug' => 'actualite'], 'en' => ['title' => '', 'slug' => '']]);

        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        self::assertCount(1, $article->getTranslations());
        self::assertSame('Actualité', $this->translation($article, $this->fr)?->getTitle());
        self::assertSame($article, $this->translation($article, $this->fr)?->getTranslatable());
    }

    public function testTheDefaultLanguageIsRequired(): void
    {
        $form = $this->submit(new Article('new'), ['fr' => ['title' => '', 'slug' => ''], 'en' => ['title' => 'News', 'slug' => 'news']]);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('translations')->get((string) $this->fr->getId())->get('title')->getErrors());
    }

    public function testAnOptionalLanguageStartedIsValidated(): void
    {
        $form = $this->submit(new Article('new'), ['fr' => ['title' => 'Actualité', 'slug' => 'actualite'], 'en' => ['title' => 'News', 'slug' => '']]);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('translations')->get((string) $this->en->getId())->get('slug')->getErrors());
    }

    public function testAnOptionalTranslationEmptiedIsRemoved(): void
    {
        $article = new Article('existing');
        $this->submit($article, ['fr' => ['title' => 'Actualité', 'slug' => 'actualite'], 'en' => ['title' => 'News', 'slug' => 'news']]);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($article);
        $entityManager->flush();

        $form = $this->submit($article, ['fr' => ['title' => 'Actualité 2', 'slug' => 'actualite'], 'en' => ['title' => '', 'slug' => '']]);
        $entityManager->flush();

        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        self::assertSame(['Actualité 2'], array_map(static fn (TranslationInterface $translation): ?string => $translation instanceof ArticleTranslation ? $translation->getTitle() : null, array_values($article->getTranslations()->toArray())));
        self::assertSame(1, $entityManager->getRepository(ArticleTranslation::class)->count([]));
    }

    public function testEveryLanguageWithoutCurrentSite(): void
    {
        self::getContainer()->get(SiteContext::class)->setSite(null);

        self::assertCount(3, $this->form(new Article('new'))->get('translations'));
    }

    public function testRendersOneTabPerLanguageTheFirstInvalidOneOpen(): void
    {
        $form = $this->submit(new Article('new'), ['fr' => ['title' => 'Actualité', 'slug' => 'actualite'], 'en' => ['title' => 'News', 'slug' => '']]);
        $template = self::getContainer()->get(Environment::class)->createTemplate("{% form_theme form '@GingermindsCore/form/theme.html.twig' %}{{ form_widget(form) }}");
        $crawler = new Crawler($template->render(['form' => $form->createView()]));

        $tabs = $crawler->filter('.gm-translations .nav-tabs .nav-link');
        self::assertSame(['FR *', 'EN'], $tabs->each(static fn (Crawler $tab): string => trim($tab->text())));
        self::assertStringContainsString('active', (string) $tabs->eq(1)->attr('class'), 'The invalid tab is open.');
        self::assertStringContainsString('text-danger', (string) $tabs->eq(1)->attr('class'));
        self::assertCount(1, $crawler->filter('.tab-pane.active #article_translations_' . $this->en->getId() . '_slug'));
    }

    /**
     * @param array<string, array<string, string>> $byIso
     *
     * @return FormInterface<Article>
     */
    private function submit(Article $article, array $byIso): FormInterface
    {
        $form = $this->form($article);
        $translations = [];

        foreach ($byIso as $iso => $fields) {
            $translations[(string) ('fr' === $iso ? $this->fr : $this->en)->getId()] = $fields;
        }

        $form->submit(['translations' => $translations]);

        return $form;
    }

    /**
     * @return FormInterface<Article>
     */
    private function form(Article $article): FormInterface
    {
        return self::getContainer()->get(FormFactoryInterface::class)->create(ArticleType::class, $article, ['csrf_protection' => false]);
    }

    private function translation(Article $article, Language $language): ?ArticleTranslation
    {
        $translation = $article->getTranslation($language, fallback: false);

        return $translation instanceof ArticleTranslation ? $translation : null;
    }
}
