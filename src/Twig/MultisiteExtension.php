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
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MultisiteExtension extends AbstractExtension implements ResetInterface
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

    public function getFunctions(): array
    {
        return [
            new TwigFunction('gm_multisite_switchable_sites', $this->switchableSites(...)),
            new TwigFunction('gm_multisite_admin_site', $this->siteContext->site(...)),
            new TwigFunction('gm_current_site', $this->siteContext->site(...)),
            new TwigFunction('gm_current_language', $this->currentLanguage(...)),
            new TwigFunction('gm_multisite_translations_enabled', $this->translations->isEnabledForSite(...)),
        ];
    }

    /**
     * The sites of the admin site switcher: none when there is only one site.
     *
     * @return list<SiteInterface>
     */
    public function switchableSites(): array
    {
        $this->sites ??= $this->siteRepository->findAllOrdered();

        return \count($this->sites) > 1 ? $this->sites : [];
    }

    public function currentLanguage(): ?LanguageInterface
    {
        return $this->languageContext->current();
    }

    public function reset(): void
    {
        $this->sites = null;
    }
}
