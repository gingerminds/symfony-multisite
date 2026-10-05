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
 * unique (`<owner>_id`, `language_id`) constraint. Already mapped fields are kept.
 */
final class TranslatableMetadataListener
{
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
        $column = $args->getEntityManager()->getConfiguration()->getNamingStrategy()->joinKeyColumnName($owner, null);

        $metadata->mapManyToOne([
            'fieldName' => 'translatable',
            'targetEntity' => $owner,
            'inversedBy' => 'translations',
            'joinColumns' => [['name' => $column, 'referencedColumnName' => 'id', 'nullable' => false, 'onDelete' => 'CASCADE']],
        ]);

        $name = $metadata->getTableName() . '_language_unique';
        $metadata->table['uniqueConstraints'][$name] ??= ['columns' => [$column, 'language_id']];
    }
}
