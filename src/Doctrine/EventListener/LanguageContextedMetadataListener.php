<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Doctrine\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Model\LanguageContextedInterface;

/**
 * Doctrine names a default join table after the declared target, here the
 * interface: LanguageContextedTrait would get `media_language_interface` /
 * `language_interface_id`. Renamed to `media_language` / `language_id`; a join
 * table named explicitly (AssociationOverride) is kept.
 */
final class LanguageContextedMetadataListener
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $metadata = $args->getClassMetadata();
        $field = LanguageContextedInterface::LANGUAGES_FIELD;

        if (
            $metadata->isMappedSuperclass
            || !is_a($metadata->getName(), LanguageContextedInterface::class, true)
            || !$metadata->hasAssociation($field)
        ) {
            return;
        }

        $mapping = $metadata->getAssociationMapping($field);
        $namingStrategy = $args->getEntityManager()->getConfiguration()->getNamingStrategy();

        $defaultName = $namingStrategy->joinTableName($metadata->getName(), LanguageInterface::class, $field);

        if (!$mapping instanceof ManyToManyOwningSideMapping || $mapping->joinTable->name !== $defaultName) {
            return;
        }

        $mapping->joinTable->name = $namingStrategy->joinTableName($metadata->getName(), Language::class, $field);

        $inverseColumn = $mapping->joinTable->inverseJoinColumns[0];
        $oldName = $inverseColumn->name;
        $newName = $namingStrategy->joinKeyColumnName(Language::class, $inverseColumn->referencedColumnName);

        if ($oldName === $newName) {
            return;
        }

        $inverseColumn->name = $newName;
        $mapping->joinTableColumns = array_values(array_map(static fn (string $column): string => $column === $oldName ? $newName : $column, $mapping->joinTableColumns));

        if (isset($mapping->relationToTargetKeyColumns[$oldName])) {
            $mapping->relationToTargetKeyColumns[$newName] = $mapping->relationToTargetKeyColumns[$oldName];
            unset($mapping->relationToTargetKeyColumns[$oldName]);
        }
    }
}
