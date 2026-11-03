<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Unit\Doctrine;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Gingerminds\MultisiteBundle\Doctrine\EventListener\TranslatableMetadataListener;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Gingerminds\MultisiteBundle\Model\TranslationTrait;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Article;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\ArticleTranslation;
use PHPUnit\Framework\TestCase;

final class TranslatableMetadataListenerTest extends TestCase
{
    public function testTheOwnerRelationIsJoinedOnTheOwnerColumn(): void
    {
        $metadata = $this->load(ArticleTranslation::class, 'article_translations');

        self::assertSame(Article::class, $metadata->getAssociationTargetClass('translatable'));
        self::assertSame('article_id', $metadata->getSingleAssociationJoinColumnName('translatable'));
        self::assertSame(['article_translations_language_unique' => ['columns' => ['article_id', 'language_id']]], $metadata->table['uniqueConstraints']);
    }

    public function testAnInterfaceOwnerIsJoinedWithoutItsSuffixAndADeclaredConstraintIsKept(): void
    {
        $metadata = $this->load(OverridableArticleTranslation::class, 'overridable_article_translations', [
            'legacy_unique' => ['columns' => ['language_id', 'overridable_article_id']],
        ]);

        self::assertSame(OverridableArticleInterface::class, $metadata->getAssociationTargetClass('translatable'));
        self::assertSame('overridable_article_id', $metadata->getSingleAssociationJoinColumnName('translatable'));
        self::assertSame(['legacy_unique'], array_keys($metadata->table['uniqueConstraints']));
    }

    /**
     * @param class-string                                $class
     * @param array<string, array{columns: list<string>}> $uniqueConstraints
     *
     * @return ClassMetadata<object>
     */
    private function load(string $class, string $table, array $uniqueConstraints = []): ClassMetadata
    {
        $metadata = new ClassMetadata($class);
        $metadata->setPrimaryTable(['name' => $table, 'uniqueConstraints' => $uniqueConstraints]);
        $configuration = new Configuration();
        $configuration->setNamingStrategy(new UnderscoreNamingStrategy(\CASE_LOWER, true));
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConfiguration')->willReturn($configuration);

        new TranslatableMetadataListener()->loadClassMetadata(new LoadClassMetadataEventArgs($metadata, $entityManager));

        return $metadata;
    }
}

interface OverridableArticleInterface extends TranslatableInterface
{
}

final class OverridableArticleTranslation implements TranslationInterface
{
    use TranslationTrait;

    public static function getTranslatableEntityClass(): string
    {
        return OverridableArticleInterface::class;
    }
}
