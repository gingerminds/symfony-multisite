<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Entity\User\BaseUser;
use Gingerminds\CoreBundle\Repository\User\UserRepository;
use Gingerminds\MultisiteBundle\Model\SiteRestrictedUserInterface;
use Gingerminds\MultisiteBundle\Model\UserSitesTrait;

/**
 * Project user restricted to some sites (overrides the core User).
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User extends BaseUser implements SiteRestrictedUserInterface
{
    use UserSitesTrait;
}
