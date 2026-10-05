<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Controller\Translation;

use Gingerminds\MultisiteBundle\Http\RefererUrl;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Gingerminds\MultisiteBundle\Translation\TranslationRefresher;
use Gingerminds\MultisiteBundle\Translation\TranslationService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Admin action refreshing the front translations cache of a site (`manage translations`).
 */
final readonly class TranslationRefreshController
{
    public const string PERMISSION = 'manage translations';
    public const string CSRF_TOKEN_ID = 'gingerminds_multisite_translations_refresh';

    public function __construct(
        private SiteRepository $sites,
        private TranslationService $translations,
        private TranslationRefresher $refresher,
        private AuthorizationCheckerInterface $authorizationChecker,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        if (!$this->authorizationChecker->isGranted(self::PERMISSION)) {
            throw new AccessDeniedHttpException();
        }

        $payload = $request->getPayload();
        $site = $this->sites->find($payload->getInt('site_id'));

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $payload->getString('_token')))) {
            $this->flash($request, 'danger', 'flash.invalid_csrf', 'GingermindsCore');
        } elseif (null === $site || !$this->translations->isEnabledForSite($site)) {
            $this->flash($request, 'danger', 'translations.site_required');
        } else {
            $this->refresher->refresh($site);
            $this->flash($request, 'success', 'translations.refresh_dispatched');
        }

        return new RedirectResponse(RefererUrl::sameHost($request, $this->urlGenerator->generate('gingerminds_multisite_site_index')));
    }

    private function flash(Request $request, string $type, string $message, string $domain = 'GingermindsMultisite'): void
    {
        $session = $request->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $this->translator->trans($message, [], $domain));
        }
    }
}
