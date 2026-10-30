<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Controller\Translation;

use Gingerminds\MultisiteBundle\Http\RefererUrl;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Gingerminds\MultisiteBundle\Translation\TranslationRefresher;
use Gingerminds\MultisiteBundle\Translation\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Admin action refreshing the front translations cache of a site (`manage translations`). An
 * invalid token is handled by the core InvalidCsrfTokenListener (route `_resource`: site).
 */
final class TranslationRefreshController extends AbstractController
{
    public const string PERMISSION = 'manage translations';
    public const string CSRF_TOKEN_ID = 'gingerminds_multisite_translations_refresh';

    public function __construct(
        private readonly SiteRepository $sites,
        private readonly TranslationService $translations,
        private readonly TranslationRefresher $refresher,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[IsGranted(self::PERMISSION)]
    #[IsCsrfTokenValid(self::CSRF_TOKEN_ID)]
    public function __invoke(Request $request): RedirectResponse
    {
        $site = $this->sites->findOneForRead($request->getPayload()->getInt('site_id'));

        if (null === $site || !$this->translations->isEnabledForSite($site)) {
            $this->addFlash('danger', $this->translator->trans('translations.site_required', [], 'GingermindsMultisite'));
        } else {
            $this->refresher->refresh($site);
            $this->addFlash('success', $this->translator->trans('translations.refresh_dispatched', [], 'GingermindsMultisite'));
        }

        return $this->redirect(RefererUrl::sameHost($request, $this->generateUrl('gingerminds_multisite_site_index')));
    }
}
