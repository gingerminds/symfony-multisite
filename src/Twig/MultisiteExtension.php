<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Twig;

use Gingerminds\MultisiteBundle\Controller\Site\SiteSwitchController;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MultisiteExtension extends AbstractExtension implements ResetInterface
{
    /** @var list<SiteInterface>|null */
    private ?array $sites = null;

    public function __construct(
        private readonly SiteRepository $siteRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('gm_multisite_switchable_sites', $this->switchableSites(...)),
            new TwigFunction('gm_multisite_admin_site', $this->adminSite(...)),
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

    /**
     * The site the admin works on (switcher choice in session), the first one by default.
     */
    public function adminSite(): ?SiteInterface
    {
        $sites = $this->switchableSites();
        $session = $this->requestStack->getCurrentRequest()?->hasPreviousSession() ? $this->requestStack->getSession() : null;
        $id = $session?->get(SiteSwitchController::SESSION_KEY);

        foreach ($sites as $site) {
            if ($site->getId() === $id) {
                return $site;
            }
        }

        return $sites[0] ?? null;
    }

    public function reset(): void
    {
        $this->sites = null;
    }
}
