<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Site;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;

/**
 * `GET /api/sites` is public (front applications list the sites before any login).
 */
#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'sites')]
#[ApiResource(
    shortName: 'Site',
    operations: [
        // Stable payload for the fronts: `default_language` is null rather than missing.
        new GetCollection(normalizationContext: ['groups' => [BaseSite::GROUP_LIST], 'skip_null_values' => false]),
    ],
    paginationClientItemsPerPage: true,
    provider: 'gingerminds_multisite.api.provider.site',
)]
class Site extends BaseSite
{
}
