<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Site;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'sites')]
class Site extends BaseSite
{
}
