<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Cache;

use Gingerminds\CoreBundle\Cache\CacheContextResolverInterface;
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;

/**
 * Replaces the core no-op cache context: the cached API responses vary with the
 * current site and language. The fallback language is not part of it: it is the
 * site default language, already determined by the site.
 */
final readonly class SiteLanguageCacheContextResolver implements CacheContextResolverInterface
{
    public function __construct(
        private SiteContext $siteContext,
        private LanguageContext $languageContext,
    ) {
    }

    public function resolve(): array
    {
        return [
            'site' => $this->siteContext->id(),
            'lang' => $this->languageContext->current()?->getId(),
        ];
    }
}
