<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Http;

use Symfony\Component\HttpFoundation\Request;

/**
 * The referring page of a POST action when it is one of this host (no open redirect).
 */
final class RefererUrl
{
    public static function sameHost(Request $request, string $fallback): string
    {
        $parts = parse_url((string) $request->headers->get('referer'));

        if (\is_array($parts) && ($parts['host'] ?? null) === $request->getHost() && isset($parts['path'])) {
            return $parts['path'] . (isset($parts['query']) ? '?' . $parts['query'] : '');
        }

        return $fallback;
    }
}
