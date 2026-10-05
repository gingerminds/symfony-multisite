<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Context;

use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The languages of a request on a site: the current one is the first
 * Accept-Language code enabled on the site, the fallback is the site default
 * language (its first language when none is flagged); the current one falls
 * back to it.
 */
class LanguageContextResolver
{
    /**
     * @return array{0: LanguageInterface|null, 1: LanguageInterface|null} current, fallback
     */
    public function resolve(SiteInterface $site, ?Request $request): array
    {
        $languages = $site->getLanguages();
        $fallback = $site->getDefaultLanguage() ?? $languages[0] ?? null;

        foreach (AcceptLanguage::parse($request?->headers->get('Accept-Language')) as $iso) {
            foreach ($languages as $language) {
                if ($language->getIso() === $iso) {
                    return [$language, $fallback];
                }
            }
        }

        return [$fallback, $fallback];
    }
}
