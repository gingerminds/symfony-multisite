<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Controller\Site;

use Gingerminds\MultisiteBundle\Context\SiteContextResolver;
use Gingerminds\MultisiteBundle\Http\RefererUrl;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

/**
 * Admin site switcher: the chosen site in session (SiteContextResolver). An invalid token is
 * handled by the core InvalidCsrfTokenListener (route `_resource`: site).
 */
final class SiteSwitchController extends AbstractController
{
    public const string SESSION_KEY = SiteContextResolver::SESSION_KEY;
    public const string CSRF_TOKEN_ID = 'gingerminds_multisite_site_switch';

    public function __construct(
        private readonly SiteRepository $sites,
    ) {
    }

    #[IsCsrfTokenValid(self::CSRF_TOKEN_ID)]
    public function __invoke(Request $request): RedirectResponse
    {
        // findOneForRead(): among the sites of the current user only.
        $site = $this->sites->findOneForRead($request->getPayload()->getInt('site_id'));

        if (null !== $site) {
            $request->getSession()->set(self::SESSION_KEY, $site->getId());
        }

        return $this->redirect(RefererUrl::sameHost($request, $this->generateUrl('gingerminds_core_dashboard')));
    }
}
