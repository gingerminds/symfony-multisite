<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\MultisiteBundle\Cache\SiteLanguageCacheContextResolver;
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\LanguageContextResolver;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Context\SiteContextResolver;
use Gingerminds\MultisiteBundle\DependencyInjection\Compiler\CacheContextResolverPass;
use Gingerminds\MultisiteBundle\EventListener\ContextFilterListener;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Symfony\Component\HttpKernel\KernelEvents;

/*
 * Current site and language, their cache context and Doctrine filters.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_multisite.context.site_resolver', SiteContextResolver::class)
        ->args([service(SiteRepository::class)]);
    $services->alias(SiteContextResolver::class, 'gingerminds_multisite.context.site_resolver');

    $services->set('gingerminds_multisite.context.site', SiteContext::class)
        ->args([service('gingerminds_multisite.context.site_resolver'), service('request_stack')])
        ->tag('kernel.reset', ['method' => 'reset']);
    $services->alias(SiteContext::class, 'gingerminds_multisite.context.site');

    $services->set('gingerminds_multisite.context.language_resolver', LanguageContextResolver::class);
    $services->alias(LanguageContextResolver::class, 'gingerminds_multisite.context.language_resolver');

    $services->set('gingerminds_multisite.context.language', LanguageContext::class)
        ->args([
            service('gingerminds_multisite.context.site'),
            service('gingerminds_multisite.context.language_resolver'),
            service('request_stack'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);
    $services->alias(LanguageContext::class, 'gingerminds_multisite.context.language');

    // Becomes `gingerminds_core.cache.context_resolver` (CacheContextResolverPass).
    $services->set(CacheContextResolverPass::RESOLVER, SiteLanguageCacheContextResolver::class)
        ->args([service('gingerminds_multisite.context.site'), service('gingerminds_multisite.context.language')]);

    $services->set('gingerminds_multisite.context.filter_listener', ContextFilterListener::class)
        ->args([
            service('doctrine'),
            service('gingerminds_multisite.context.site'),
            service('gingerminds_multisite.context.language'),
        ])
        // After the firewall (8), before the core API response cache (5) and API Platform (4).
        ->tag('kernel.event_listener', ['event' => KernelEvents::REQUEST, 'method' => 'onKernelRequest', 'priority' => 6]);
};
