<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Context;

use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Http\AdminPath;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Gingerminds\MultisiteBundle\Security\SiteAccess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The site of a request, in this order:
 *  1. the admin site switcher choice (`admin_site_id` session key, only when a session exists
 *     and the request is not stateless);
 *  2. the `X-Site-Id` header (site id, or code);
 *  3. the request host, contained in a site URL;
 *  4. the first site, or a 404 out of the admin with `unknown_host: not_found`.
 *
 * Only among the sites of the current user (SiteAccess): another one is skipped, except the
 * site the `X-Site-Id` header asks for explicitly (403).
 */
class SiteContextResolver
{
    public const string SESSION_KEY = 'admin_site_id';
    public const string HEADER = 'X-Site-Id';

    /** `unknown_host` values. */
    public const string UNKNOWN_HOST_FIRST_SITE = 'first_site';
    public const string UNKNOWN_HOST_NOT_FOUND = 'not_found';

    public function __construct(
        protected readonly SiteRepository $sites,
        protected readonly SiteAccess $access,
        protected readonly string $unknownHost = self::UNKNOWN_HOST_FIRST_SITE,
        protected readonly string $adminPrefix = 'admin',
    ) {
    }

    /**
     * @throws NotFoundHttpException with `unknown_host: not_found`, when nothing names a site out of the admin
     */
    public function resolve(Request $request): ?SiteInterface
    {
        $site = $this->allowed($this->fromSession($request)) ?? $this->fromAllowedHeader($request);

        if ($site instanceof SiteInterface) {
            return $site;
        }

        $hostSite = $this->sites->findOneByHost($request->getHost());

        // The admin is usually served on a back-office domain: it always falls back.
        if (!$hostSite instanceof SiteInterface && self::UNKNOWN_HOST_NOT_FOUND === $this->unknownHost && !AdminPath::matches($request, $this->adminPrefix)) {
            throw new NotFoundHttpException(\sprintf('No site matches the "%s" host.', $request->getHost()));
        }

        return $this->allowed($hostSite) ?? $this->sites->findFirst();
    }

    /**
     * @throws AccessDeniedHttpException when the header names a site the user may not access
     */
    protected function fromAllowedHeader(Request $request): ?SiteInterface
    {
        $site = $this->fromHeader($request);

        if ($site instanceof SiteInterface && !$this->access->allows($site)) {
            throw new AccessDeniedHttpException(\sprintf('The site of the %s header is not allowed to the current user.', self::HEADER));
        }

        return $site;
    }

    protected function allowed(?SiteInterface $site): ?SiteInterface
    {
        return $site instanceof SiteInterface && $this->access->allows($site) ? $site : null;
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
