<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Context;

use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * The site of a request, in this order:
 *  1. the admin site switcher choice (`admin_site_id` session key, only when a session exists
 *     and the request is not stateless);
 *  2. the `X-Site-Id` header (site id, or code);
 *  3. the request host, contained in a site URL;
 *  4. the first site.
 */
class SiteContextResolver
{
    public const string SESSION_KEY = 'admin_site_id';
    public const string HEADER = 'X-Site-Id';

    public function __construct(
        protected readonly SiteRepository $sites,
    ) {
    }

    public function resolve(Request $request): ?SiteInterface
    {
        return $this->fromSession($request)
            ?? $this->fromHeader($request)
            ?? $this->sites->findOneByHost($request->getHost())
            ?? $this->sites->findFirst();
    }

    protected function fromSession(Request $request): ?SiteInterface
    {
        // Never on a stateless request (API, even called with the admin session cookie
        // from Swagger UI), never starting a session.
        if ($request->attributes->getBoolean('_stateless') || !$request->hasPreviousSession()) {
            return null;
        }

        $id = $request->getSession()->get(self::SESSION_KEY);

        return \is_int($id) || (\is_string($id) && ctype_digit($id)) ? $this->sites->find((int) $id) : null;
    }

    protected function fromHeader(Request $request): ?SiteInterface
    {
        $value = trim((string) $request->headers->get(self::HEADER));

        return '' === $value ? null : $this->sites->findOneByIdOrCode($value);
    }
}
