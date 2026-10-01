<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional\Context;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Gingerminds\CoreBundle\Cache\CacheContextResolverInterface;
use Gingerminds\MultisiteBundle\Cache\SiteLanguageCacheContextResolver;
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Doctrine\SiteQuery;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Article;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\ArticleTranslation;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Media;
use Gingerminds\MultisiteBundle\Tests\Functional\ApiTestCase;

/**
 * Context services outside of an HTTP request (command, message handler): no
 * Doctrine filter, explicit context.
 */
final class ContextServicesTest extends ApiTestCase
{
    public function testNoRequestNoContext(): void
    {
        $this->fixtures->site('alpha');

        self::assertNull($this->siteContext()->site());
        self::assertNull($this->languageContext()->current());
    }

    public function testNewSiteContextedEntityGetsTheCurrentSite(): void
    {
        $alpha = $this->fixtures->site('alpha');
        $beta = $this->fixtures->site('beta');
        $this->siteContext()->setSite($alpha);

        $article = new Article('new');
        $explicit = new Article('explicit');
        $explicit->setSite($beta);
        $this->entityManager()->persist($article);
        $this->entityManager()->persist($explicit);
        $this->entityManager()->flush();

        self::assertSame($alpha, $article->getSite());
        self::assertSame($beta, $explicit->getSite(), 'An explicit site is kept.');
    }

    public function testTheCoreCacheContextVariesWithTheSiteAndLanguage(): void
    {
        $fr = $this->fixtures->language('fr');
        $alpha = $this->fixtures->site('alpha', [$fr], $fr);
        $this->siteContext()->setSite($alpha);
        $this->languageContext()->setLanguages($fr);

        $resolver = self::getContainer()->get(CacheContextResolverInterface::class);

        self::assertInstanceOf(SiteLanguageCacheContextResolver::class, $resolver);
        self::assertSame(['site' => $alpha->getId(), 'lang' => $fr->getId()], $resolver->resolve());
    }

    public function testSiteQueryRestrictsToASiteAndTheSharedRows(): void
    {
        $alpha = $this->fixtures->site('alpha');
        $beta = $this->fixtures->site('beta');

        foreach (['a' => $alpha, 'b' => $beta, 'shared' => null] as $code => $site) {
            $article = new Article($code);
            $article->setSite($site);
            $this->entityManager()->persist($article);
        }

        $this->entityManager()->flush();

        $qb = $this->entityManager()->getRepository(Article::class)->createQueryBuilder('a')->orderBy('a.id');
        $codes = array_map(static fn (Article $article): string => $article->getCode(), SiteQuery::restrict($qb, 'a', $beta)->getQuery()->getResult());

        self::assertSame(['b', 'shared'], $codes);
    }

    public function testTranslationsLookupAndFallback(): void
    {
        $fr = $this->fixtures->language('fr');
        $en = $this->fixtures->language('en');
        $de = $this->fixtures->language('de');
        $article = new Article('translated');

        foreach (['fr' => $fr, 'en' => $en] as $iso => $language) {
            $translation = new ArticleTranslation();
            $translation->setLanguage($language);
            $translation->setTitle('title ' . $iso);
            $article->addTranslation($translation);
        }

        // No language context: the first translation.
        self::assertSame('title fr', $article->getTitle());

        $this->languageContext()->setLanguages($de, $en);
        $this->entityManager()->persist($article);
        $this->entityManager()->flush();

        self::assertSame('title en', $article->getTitle(), 'Current language (de) missing: the fallback one.');
        self::assertSame('title fr', $this->title($article->getTranslation($fr)));
        self::assertSame('title fr', $this->title($article->getTranslation($fr->getId())));
        self::assertSame('title en', $this->title($article->getTranslation($de)), 'Missing language: the fallback one.');
        self::assertNull($article->getTranslation($de, fallback: false));
        self::assertSame($article, $article->getTranslation($fr)?->getTranslatable());
    }

    public function testMappingConventions(): void
    {
        $translation = $this->entityManager()->getClassMetadata(ArticleTranslation::class);
        $article = $this->entityManager()->getClassMetadata(Article::class);
        $media = $this->entityManager()->getClassMetadata(Media::class)->getAssociationMapping('languages');

        self::assertSame('article_id', $translation->getSingleAssociationJoinColumnName('translatable'));
        self::assertSame(['article_id', 'language_id'], $translation->table['uniqueConstraints']['article_translations_language_unique']['columns'] ?? null);
        self::assertSame(ArticleTranslation::class, $article->getAssociationTargetClass('translations'));
        self::assertSame('site_id', $article->getSingleAssociationJoinColumnName('site'));
        self::assertInstanceOf(ManyToManyOwningSideMapping::class, $media);
        self::assertSame('media_language', $media->joinTable->name);
        self::assertSame(['media_id', 'language_id'], $media->joinTableColumns);
    }

    private function title(?object $translation): ?string
    {
        return $translation instanceof ArticleTranslation ? $translation->getTitle() : null;
    }

    private function siteContext(): SiteContext
    {
        return self::getContainer()->get(SiteContext::class);
    }

    private function languageContext(): LanguageContext
    {
        return self::getContainer()->get(LanguageContext::class);
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
