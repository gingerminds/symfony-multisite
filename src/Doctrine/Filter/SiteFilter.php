<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;

/**
 * `gingerminds_site`: the SiteContextedInterface rows of the current site and the
 * shared ones (no site); nothing without current site. Enabled on every HTTP
 * request by ContextFilterListener; disable it for a cross-site query:
 * `$entityManager->getFilters()->disable(SiteFilter::NAME)`.
 */
final class SiteFilter extends SQLFilter
{
    public const string NAME = 'gingerminds_site';
    public const string PARAMETER = 'site_id';

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!is_a($targetEntity->getName(), SiteContextedInterface::class, true) || !$targetEntity->hasAssociation('site')) {
            return '';
        }

        if (!$this->hasParameter(self::PARAMETER)) {
            return '1 = 0';
        }

        $column = $targetTableAlias . '.' . $targetEntity->getSingleAssociationJoinColumnName('site');

        return \sprintf('(%s = %s OR %s IS NULL)', $column, $this->getParameter(self::PARAMETER), $column);
    }
}
