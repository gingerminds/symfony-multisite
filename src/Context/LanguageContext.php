<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Context;

use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

/**
 * "The current language" of the current site (LanguageContextResolver) and its
 * fallback, resolved once per site and main request, or set explicitly.
 */
class LanguageContext implements ResetInterface
{
    private ?LanguageInterface $current = null;
    private ?LanguageInterface $fallback = null;
    private bool $explicit = false;
    private ?string $resolvedFor = null;

    public function __construct(
        private readonly SiteContext $siteContext,
        private readonly LanguageContextResolver $resolver,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function current(): ?LanguageInterface
    {
        $this->resolve();

        return $this->current;
    }

    public function fallback(): ?LanguageInterface
    {
        $this->resolve();

        return $this->fallback;
    }

    public function has(): bool
    {
        return $this->current() instanceof LanguageInterface || $this->fallback() instanceof LanguageInterface;
    }

    /**
     * Ids of the languages to try for a translation, best first (current, then fallback).
     *
     * @return list<int>
     */
    public function ids(): array
    {
        $ids = [];

        foreach ([$this->current(), $this->fallback()] as $language) {
            $id = $language?->getId();

            if (null !== $id && !\in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * Forces the languages, instead of resolving them from the request.
     */
    public function setLanguages(?LanguageInterface $current, ?LanguageInterface $fallback = null): void
    {
        $this->current = $current;
        $this->fallback = $fallback ?? $current;
        $this->explicit = true;
    }

    public function reset(): void
    {
        $this->current = null;
        $this->fallback = null;
        $this->explicit = false;
        $this->resolvedFor = null;
    }

    private function resolve(): void
    {
        if ($this->explicit) {
            return;
        }

        $site = $this->siteContext->site();
        $request = $this->requestStack->getMainRequest();
        $key = spl_object_id($site ?? $this) . '/' . ($request instanceof Request ? spl_object_id($request) : '-');

        if ($key === $this->resolvedFor) {
            return;
        }

        $this->resolvedFor = $key;
        [$this->current, $this->fallback] = $site instanceof SiteInterface ? $this->resolver->resolve($site, $request) : [null, null];
    }
}
