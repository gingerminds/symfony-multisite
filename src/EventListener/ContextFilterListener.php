<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Doctrine\Filter\LanguageFilter;
use Gingerminds\MultisiteBundle\Doctrine\Filter\SiteFilter;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Http\AdminPath;
use Gingerminds\MultisiteBundle\Security\SiteAccess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Translation\LocaleSwitcher;

/**
 * Enables the site and language Doctrine filters on the main request (Laravel
 * global scopes), with the current site and language. Commands and message
 * handlers are not filtered. A user allowed no site (SiteAccess) is denied.
 *
 * Out of the admin, the current language is also the Symfony locale (translator, Intl,
 * Twig): the admin language is the one of its interface, not of the edited content.
 */
final readonly class ContextFilterListener
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private SiteContext $siteContext,
        private LanguageContext $languageContext,
        private SiteAccess $access,
        private string $adminPrefix,
        private ?LocaleSwitcher $localeSwitcher = null,
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

        $language = $this->languageContext->current();

        if ($language instanceof LanguageInterface && !AdminPath::matches($event->getRequest(), $this->adminPrefix)) {
            $this->setLocale($event->getRequest(), $language);
        }

        $this->enableFilters($siteId, $language?->getId());
    }

    /**
     * After the LocaleListener and the LocaleAwareListener: the request and the translator.
     */
    private function setLocale(Request $request, LanguageInterface $language): void
    {
        $locale = str_replace('-', '_', (string) $language->getIso());
        $request->setLocale($locale);
        $this->localeSwitcher?->setLocale($locale);
    }

    private function enableFilters(?int $siteId, ?int $languageId): void
    {
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
