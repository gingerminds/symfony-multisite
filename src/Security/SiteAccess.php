<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Security;

use Gingerminds\CoreBundle\Entity\User\UserInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\SiteRestrictedUserInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * The sites a user may work on (the current one by default): every site, unless the user is
 * a SiteRestrictedUserInterface without all sites and not a super admin. No user (anonymous
 * request, command) means every site.
 */
class SiteAccess
{
    /**
     * Grants editing the sites of the other users (UserSitesTypeExtension).
     */
    public const string ASSIGN_PERMISSION = 'assign sites';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * The ids of the allowed sites, null for every site.
     *
     * @return list<int>|null
     */
    public function allowedSiteIds(?object $user = null): ?array
    {
        $user ??= $this->security->getUser();

        if (!$user instanceof SiteRestrictedUserInterface || $user->hasAllSites() || ($user instanceof UserInterface && $user->isSuperAdmin())) {
            return null;
        }

        $ids = [];

        foreach ($user->getSites() as $site) {
            if (null !== $site->getId()) {
                $ids[] = $site->getId();
            }
        }

        return $ids;
    }

    public function allows(SiteInterface $site, ?object $user = null): bool
    {
        $ids = $this->allowedSiteIds($user);

        return null === $ids || \in_array($site->getId(), $ids, true);
    }

    public function isRestricted(?object $user = null): bool
    {
        return null !== $this->allowedSiteIds($user);
    }

    /**
     * Whether the current user edits the sites of `$user`: a super admin, or the holder of
     * the `assign sites` permission on another account than their own.
     */
    public function canAssign(SiteRestrictedUserInterface $user): bool
    {
        $assigner = $this->security->getUser();

        if (!$assigner instanceof UserInterface) {
            return false;
        }

        return $assigner->isSuperAdmin()
            || ($assigner->hasPermission(self::ASSIGN_PERMISSION) && $user !== $assigner);
    }
}
