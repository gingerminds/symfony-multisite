<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Doctrine\Filter\LanguageFilter;
use Gingerminds\MultisiteBundle\Doctrine\Filter\SiteFilter;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Enables the site and language Doctrine filters on the main request (Laravel
 * global scopes), with the current site and language. Commands and message
 * handlers are not filtered.
 */
final readonly class ContextFilterListener
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private SiteContext $siteContext,
        private LanguageContext $languageContext,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $siteId = $this->siteContext->id();
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
