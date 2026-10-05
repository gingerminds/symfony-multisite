<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Application\Override;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Entity\Site\BaseSite;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;

/**
 * Project override of the bundle Site (loaded in the `override` environment only).
 */
#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'sites')]
class Site extends BaseSite
{
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $brand = null;

    public function getBrand(): ?string
    {
        return $this->brand;
    }
}
