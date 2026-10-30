<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Http;

use Symfony\Component\HttpFoundation\Request;

/**
 * Whether a request targets the admin (`gingerminds_core.admin_prefix`).
 */
final class AdminPath
{
    public static function matches(Request $request, string $adminPrefix): bool
    {
        $prefix = '/' . trim($adminPrefix, '/');
        $path = $request->getPathInfo();

        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }
}
