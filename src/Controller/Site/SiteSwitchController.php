<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Controller\Site;

use Gingerminds\MultisiteBundle\Context\SiteContextResolver;
use Gingerminds\MultisiteBundle\Http\RefererUrl;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class SiteSwitchController
{
    public const string SESSION_KEY = SiteContextResolver::SESSION_KEY;
    public const string CSRF_TOKEN_ID = 'gingerminds_multisite_site_switch';

    public function __construct(
        private SiteRepository $sites,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $payload = $request->getPayload();
        $token = new CsrfToken(self::CSRF_TOKEN_ID, $payload->getString('_token'));
        $site = $this->csrfTokenManager->isTokenValid($token) ? $this->sites->find($payload->getInt('site_id')) : null;

        if (null !== $site) {
            $request->getSession()->set(self::SESSION_KEY, $site->getId());
        }

        return new RedirectResponse(RefererUrl::sameHost($request, $this->urlGenerator->generate('gingerminds_core_dashboard')));
    }
}
