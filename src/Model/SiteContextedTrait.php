<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

/**
 * Implements SiteContextedInterface: nullable `site_id` (null: shared by every
 * site). Change the deletion behaviour with an #[ORM\AssociationOverride] if needed.
 */
trait SiteContextedTrait
{
    #[ORM\ManyToOne(targetEntity: SiteInterface::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: true, onDelete: 'CASCADE')]
    protected ?SiteInterface $site = null;

    public function getSite(): ?SiteInterface
    {
        return $this->site;
    }

    public function setSite(?SiteInterface $site): void
    {
        $this->site = $site;
    }
}
