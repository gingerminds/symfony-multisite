<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

use Doctrine\Common\Collections\Collection;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

/**
 * A user whose access may be limited to some sites (UserSitesTrait maps it). A user
 * without it accesses every site.
 */
interface SiteRestrictedUserInterface
{
    public function getId(): int|string|null;

    /**
     * Every site, present and future (the sites are ignored).
     */
    public function hasAllSites(): bool;

    public function setAllSites(bool $allSites): void;

    /**
     * @return Collection<int, SiteInterface>
     */
    public function getSites(): Collection;

    /**
     * @param iterable<SiteInterface> $sites
     */
    public function setSites(iterable $sites): void;
}
