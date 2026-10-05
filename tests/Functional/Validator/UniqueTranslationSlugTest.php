<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use Gingerminds\MultisiteBundle\Entity\Site\Site;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Article;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\ArticleTranslation;
use Gingerminds\MultisiteBundle\Tests\Functional\ApiTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UniqueTranslationSlugTest extends ApiTestCase
{
    private Language $fr;
    private Language $en;
    private Site $alpha;
    private Site $beta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fr = $this->fixtures->language('fr');
        $this->en = $this->fixtures->language('en');
        $this->alpha = $this->fixtures->site('alpha', [$this->fr, $this->en], $this->fr);
        $this->beta = $this->fixtures->site('beta', [$this->fr], $this->fr);
    }

    public function testSlugIsUniquePerSiteAndLanguage(): void
    {
        $this->article($this->alpha, $this->fr, 'news', persist: true);

        self::assertSame(['slug'], $this->violations($this->article($this->alpha, $this->fr, 'news')));
        self::assertSame([], $this->violations($this->article($this->alpha, $this->en, 'news')), 'Another language.');
        self::assertSame([], $this->violations($this->article($this->beta, $this->fr, 'news')), 'Another site.');
        self::assertSame([], $this->violations($this->article($this->alpha, $this->fr, 'other')));
    }

    public function testASharedRowConflictsWithEverySite(): void
    {
        $this->article(null, $this->fr, 'everywhere', persist: true);

        self::assertSame(['slug'], $this->violations($this->article($this->beta, $this->fr, 'everywhere')));
    }

    public function testATranslationDoesNotConflictWithItself(): void
    {
        $translation = $this->article($this->alpha, $this->fr, 'news', persist: true);

        self::assertSame([], $this->violations($translation));
    }

    /**
     * @return list<string> property paths
     */
    private function violations(ArticleTranslation $translation): array
    {
        $paths = [];

        foreach (self::getContainer()->get(ValidatorInterface::class)->validate($translation) as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        return $paths;
    }

    private function article(?Site $site, Language $language, string $slug, bool $persist = false): ArticleTranslation
    {
        $article = new Article('article-' . $slug);
        $article->setSite($site);
        $translation = new ArticleTranslation();
        $translation->setLanguage($language);
        $translation->setTitle('Title');
        $translation->setSlug($slug);
        $article->addTranslation($translation);

        if ($persist) {
            $entityManager = self::getContainer()->get(EntityManagerInterface::class);
            $entityManager->persist($article);
            $entityManager->flush();
        }

        return $translation;
    }
}
