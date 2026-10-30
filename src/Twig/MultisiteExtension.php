<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Twig;

use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Gingerminds\MultisiteBundle\Translation\TranslationService;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * Twig functions of the multisite context. Attribute based: built on the first call, not with
 * the Twig environment.
 */
final class MultisiteExtension implements ResetInterface
{
    /** @var list<SiteInterface>|null */
    private ?array $sites = null;

    public function __construct(
        private readonly SiteRepository $siteRepository,
        private readonly SiteContext $siteContext,
        private readonly LanguageContext $languageContext,
        private readonly TranslationService $translations,
    ) {
    }

    /**
     * The sites of the admin site switcher, those of the current user: none when there is only one.
     *
     * @return list<SiteInterface>
     */
    #[AsTwigFunction('gm_multisite_switchable_sites')]
    public function switchableSites(): array
    {
        $this->sites ??= $this->siteRepository->findAllOrdered();

        return \count($this->sites) > 1 ? $this->sites : [];
    }

    #[AsTwigFunction('gm_current_site')]
    public function currentSite(): ?SiteInterface
    {
        return $this->siteContext->site();
    }

    #[AsTwigFunction('gm_current_language')]
    public function currentLanguage(): ?LanguageInterface
    {
        return $this->languageContext->current();
    }

    #[AsTwigFunction('gm_multisite_translations_enabled')]
    public function translationsEnabled(SiteInterface $site): bool
    {
        return $this->translations->isEnabledForSite($site);
    }

    public function reset(): void
    {
        $this->sites = null;
    }
}
