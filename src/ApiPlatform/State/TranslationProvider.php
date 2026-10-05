<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gingerminds\MultisiteBundle\ApiResource\Translation;
use Gingerminds\MultisiteBundle\Context\AcceptLanguage;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Gingerminds\MultisiteBundle\Translation\TranslationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<Translation>
 */
final readonly class TranslationProvider implements ProviderInterface
{
    public function __construct(
        private TranslationService $translations,
        private SiteContext $siteContext,
        private SiteRepository $sites,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return list<Translation>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $context['request'] ?? $this->requestStack->getCurrentRequest();
        $request = $request instanceof Request ? $request : null;
        $site = $this->site($request);

        if (!$site instanceof SiteInterface) {
            return [];
        }

        $translations = $this->translations->getTranslationsForSite($site);
        $locale = AcceptLanguage::primary($request?->headers->get('Accept-Language'));

        if (null !== $locale) {
            $translations = array_intersect_key($translations, [$locale => true]);
        }

        $resources = [];

        foreach ($translations as $code => $values) {
            $resources[] = new Translation((string) $code, $values);
        }

        return $resources;
    }

    /**
     * `?site=` (id or code), else the current site (X-Site-Id header, host...).
     */
    private function site(?Request $request): ?SiteInterface
    {
        $site = trim($request?->query->getString('site') ?? '');

        return '' !== $site ? $this->sites->findOneByIdOrCode($site) : $this->siteContext->site();
    }
}
