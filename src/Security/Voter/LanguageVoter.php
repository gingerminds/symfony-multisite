<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Security\Voter;

use Gingerminds\CoreBundle\Security\Voter\AbstractResourceVoter;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * `view|edit|delete languages` permissions.
 */
class LanguageVoter extends AbstractResourceVoter
{
    protected function getResourceName(): string
    {
        return 'language';
    }

    protected function getSubjectClass(): string
    {
        return LanguageInterface::class;
    }

    protected function getPermissionName(): string
    {
        return 'languages';
    }
}
