<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\Filter;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

/**
 * Implements SiteRestrictedUserInterface on the project user: `all_sites` (true by default)
 * or the `user_sites` ones. A user left without site, e.g. once their sites are deleted,
 * accesses none.
 */
trait UserSitesTrait
{
    #[ORM\Column(name: 'all_sites', options: ['default' => true])]
    protected bool $allSites = true;

    /**
     * @var Collection<int, SiteInterface>
     */
    #[ORM\ManyToMany(targetEntity: SiteInterface::class)]
    #[ORM\JoinTable(name: 'user_sites')]
    #[ORM\JoinColumn(name: 'user_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'site_id', onDelete: 'CASCADE')]
    #[Filter(type: Filter::RELATION)]
    protected Collection $sites;

    public function hasAllSites(): bool
    {
        return $this->allSites;
    }

    public function setAllSites(bool $allSites): void
    {
        $this->allSites = $allSites;
    }

    public function getSites(): Collection
    {
        return $this->sites ??= new ArrayCollection();
    }

    public function setSites(iterable $sites): void
    {
        $kept = [];

        foreach ($sites as $site) {
            $kept[] = $site;

            if (!$this->getSites()->contains($site)) {
                $this->getSites()->add($site);
            }
        }

        foreach ($this->getSites() as $site) {
            if (!\in_array($site, $kept, true)) {
                $this->getSites()->removeElement($site);
            }
        }
    }
}
