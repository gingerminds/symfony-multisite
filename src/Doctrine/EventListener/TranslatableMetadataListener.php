<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Doctrine\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;

/**
 * Maps the relation between a TranslatableInterface entity and its
 * TranslationInterface entity (Laravel TranslatableModelTrait conventions):
 * `translations` one-to-many on the owner, `translatable` many-to-one on the
 * translation, joined on `<owner>_id` (e.g. `product_id`, cascade delete), and a
 * unique (`<owner>_id`, `language_id`) constraint unless one is declared on these columns.
 * Already mapped fields are kept. The owner may be an interface (an overridable entity, resolved by
 * `resolve_target_entities`): its column drops the `Interface` suffix (`ProductInterface` →
 * `product_id`); the listener runs before the Doctrine resolver (PRIORITY).
 */
final class TranslatableMetadataListener
{
    public const int PRIORITY = 10;

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $metadata = $args->getClassMetadata();

        if ($metadata->isMappedSuperclass) {
            return;
        }

        $class = $metadata->getName();

        if (is_a($class, TranslatableInterface::class, true) && !$metadata->hasAssociation('translations')) {
            $metadata->mapOneToMany([
                'fieldName' => 'translations',
                'targetEntity' => $class::getTranslationEntityClass(),
                'mappedBy' => 'translatable',
                'cascade' => ['persist', 'remove'],
                'orphanRemoval' => true,
            ]);
        }

        if (is_a($class, TranslationInterface::class, true) && !$metadata->hasAssociation('translatable')) {
            $this->mapTranslation($metadata, $class::getTranslatableEntityClass(), $args);
        }
    }

    /**
     * @param ClassMetadata<object> $metadata
     * @param class-string          $owner
     */
    private function mapTranslation(ClassMetadata $metadata, string $owner, LoadClassMetadataEventArgs $args): void
    {
        /** @var class-string $ownerName only used to derive the join column name, need not exist */
        $ownerName = interface_exists($owner) ? (string) preg_replace('/Interface$/', '', $owner) : $owner;
        $column = $args->getEntityManager()->getConfiguration()->getNamingStrategy()->joinKeyColumnName($ownerName, null);

        $metadata->mapManyToOne([
            'fieldName' => 'translatable',
            'targetEntity' => $owner,
            'inversedBy' => 'translations',
            'joinColumns' => [['name' => $column, 'referencedColumnName' => 'id', 'nullable' => false, 'onDelete' => 'CASCADE']],
        ]);

        $columns = [$column, 'language_id'];

        foreach ($metadata->table['uniqueConstraints'] ?? [] as $constraint) {
            $declared = $constraint['columns'] ?? [];

            if (\count($declared) === \count($columns) && [] === array_diff($columns, $declared)) {
                return;
            }
        }

        $metadata->table['uniqueConstraints'][$metadata->getTableName() . '_language_unique'] ??= ['columns' => $columns];
    }
}
