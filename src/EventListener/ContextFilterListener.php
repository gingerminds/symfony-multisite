<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Doctrine\Filter\LanguageFilter;
use Gingerminds\MultisiteBundle\Doctrine\Filter\SiteFilter;
use Gingerminds\MultisiteBundle\Security\SiteAccess;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Enables the site and language Doctrine filters on the main request (Laravel
 * global scopes), with the current site and language. Commands and message
 * handlers are not filtered. A user allowed no site (SiteAccess) is denied.
 */
final readonly class ContextFilterListener
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private SiteContext $siteContext,
        private LanguageContext $languageContext,
        private SiteAccess $access,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $siteId = $this->siteContext->id();

        if (null === $siteId && $this->access->isRestricted()) {
            throw new AccessDeniedHttpException('No site is allowed to the current user.');
        }

        $languageId = $this->languageContext->current()?->getId();

        foreach ($this->doctrine->getManagers() as $manager) {
            if (!$manager instanceof EntityManagerInterface) {
                continue;
            }

            $filters = $manager->getFilters();

            if ($filters->has(SiteFilter::NAME)) {
                $siteFilter = $filters->enable(SiteFilter::NAME);

                if (null !== $siteId) {
                    $siteFilter->setParameter(SiteFilter::PARAMETER, $siteId, 'integer');
                }
            }

            if ($filters->has(LanguageFilter::NAME) && null !== $languageId) {
                $filters->enable(LanguageFilter::NAME)->setParameter(LanguageFilter::PARAMETER, $languageId, 'integer');
            }
        }
    }
}
