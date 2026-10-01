<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Security\Voter;

use Gingerminds\CoreBundle\Security\Voter\AbstractResourceVoter;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

class SiteVoter extends AbstractResourceVoter
{
    protected function getResourceName(): string
    {
        return 'site';
    }

    protected function getSubjectClass(): string
    {
        return SiteInterface::class;
    }

    protected function getPermissionName(): string
    {
        return 'sites';
    }
}
