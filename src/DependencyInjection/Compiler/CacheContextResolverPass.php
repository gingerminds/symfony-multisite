<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Points the core cache context resolver to the site/language one, whatever the
 * bundle registration order (an alias set by an extension would depend on it).
 */
final class CacheContextResolverPass implements CompilerPassInterface
{
    public const string CORE_RESOLVER = 'gingerminds_core.cache.context_resolver';
    public const string RESOLVER = 'gingerminds_multisite.cache.context_resolver';

    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(self::RESOLVER) && ($container->hasAlias(self::CORE_RESOLVER) || $container->hasDefinition(self::CORE_RESOLVER))) {
            $container->setAlias(self::CORE_RESOLVER, self::RESOLVER);
        }
    }
}
