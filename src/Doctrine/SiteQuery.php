<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

/**
 * Explicit site scope of a SiteContextedInterface query (Laravel `scopeForSite`),
 * e.g. with the `gingerminds_site` filter disabled.
 */
final class SiteQuery
{
    /**
     * Rows of `$site` and the shared ones (no site).
     */
    public static function restrict(QueryBuilder $queryBuilder, string $alias, SiteInterface|int $site): QueryBuilder
    {
        $parameter = 'gm_site_' . $alias;

        return $queryBuilder
            ->andWhere(\sprintf('%1$s.site = :%2$s OR %1$s.site IS NULL', $alias, $parameter))
            ->setParameter($parameter, $site instanceof SiteInterface ? $site->getId() : $site);
    }
}
