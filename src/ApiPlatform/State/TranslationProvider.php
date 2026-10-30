<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gingerminds\MultisiteBundle\ApiResource\Translation;
use Gingerminds\MultisiteBundle\Context\LanguageContextResolver;
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

        if ($request instanceof Request && '' !== trim((string) $request->headers->get('Accept-Language')) && [] !== $translations) {
            // The site default language first: the answer when the request accepts none.
            $codes = array_map(strval(...), array_keys($translations));
            $codes = array_values(array_unique([...array_intersect([(string) $site->getDefaultLanguage()?->getIso()], $codes), ...$codes]));
            $locale = LanguageContextResolver::preferred($request, $codes);
            $translations = array_intersect_key($translations, [(string) $locale => true]);
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
