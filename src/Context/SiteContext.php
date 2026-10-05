<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Context;

use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

/**
 * "The current site": resolved once per main request (SiteContextResolver), or
 * set explicitly where there is no request (command, message handler, test).
 */
class SiteContext implements ResetInterface
{
    private ?SiteInterface $site = null;
    private bool $explicit = false;
    private ?Request $resolvedFor = null;
    private bool $resolved = false;

    public function __construct(
        private readonly SiteContextResolver $resolver,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function site(): ?SiteInterface
    {
        if ($this->explicit) {
            return $this->site;
        }

        $request = $this->requestStack->getMainRequest();

        if (!$this->resolved || $request !== $this->resolvedFor) {
            $this->site = $request instanceof Request ? $this->resolver->resolve($request) : null;
            $this->resolvedFor = $request;
            $this->resolved = true;
        }

        return $this->site;
    }

    public function id(): ?int
    {
        return $this->site()?->getId();
    }

    public function has(): bool
    {
        return $this->site() instanceof SiteInterface;
    }

    /**
     * Forces the current site (null: none), instead of resolving it from the request.
     */
    public function setSite(?SiteInterface $site): void
    {
        $this->site = $site;
        $this->explicit = true;
    }

    public function reset(): void
    {
        $this->site = null;
        $this->explicit = false;
        $this->resolvedFor = null;
        $this->resolved = false;
    }
}
