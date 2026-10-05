<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Unit\Model;

use Gingerminds\MultisiteBundle\Exception\MappingException;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableTrait;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Article;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\ArticleTranslation;
use PHPUnit\Framework\TestCase;

final class TranslatableConventionsTest extends TestCase
{
    public function testDefaultClassesFollowTheTranslationSuffix(): void
    {
        self::assertSame(ArticleTranslation::class, Article::getTranslationEntityClass());
        self::assertSame(Article::class, ArticleTranslation::getTranslatableEntityClass());
    }

    public function testAMissingTranslationClassIsExplained(): void
    {
        $entity = new class implements TranslatableInterface {
            use TranslatableTrait;
        };

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('or override "' . $entity::class . '::getTranslationEntityClass()"');

        $entity::getTranslationEntityClass();
    }
}
