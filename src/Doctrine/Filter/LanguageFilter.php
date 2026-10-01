<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Gingerminds\MultisiteBundle\Model\LanguageContextedInterface;

/**
 * `gingerminds_language`: the LanguageContextedInterface rows attached to the
 * current language; no restriction without current language. Enabled on every
 * HTTP request by ContextFilterListener.
 */
final class LanguageFilter extends SQLFilter
{
    public const string NAME = 'gingerminds_language';
    public const string PARAMETER = 'language_id';

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (
            !is_a($targetEntity->getName(), LanguageContextedInterface::class, true)
            || !$this->hasParameter(self::PARAMETER)
            || !$targetEntity->hasAssociation(LanguageContextedInterface::LANGUAGES_FIELD)
        ) {
            return '';
        }

        $mapping = $targetEntity->getAssociationMapping(LanguageContextedInterface::LANGUAGES_FIELD);

        if (!$mapping instanceof ManyToManyOwningSideMapping) {
            throw new \LogicException(\sprintf('"%s::$%s" must be a many-to-many owning side.', $targetEntity->getName(), LanguageContextedInterface::LANGUAGES_FIELD));
        }

        $joinTable = $mapping->joinTable;
        $ownerColumn = $joinTable->joinColumns[0];

        return \sprintf(
            'EXISTS (SELECT 1 FROM %1$s gm_lc WHERE gm_lc.%2$s = %3$s.%4$s AND gm_lc.%5$s = %6$s)',
            $joinTable->name,
            $ownerColumn->name,
            $targetTableAlias,
            $ownerColumn->referencedColumnName,
            $joinTable->inverseJoinColumns[0]->name,
            $this->getParameter(self::PARAMETER),
        );
    }
}
