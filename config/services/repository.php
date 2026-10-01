<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\MultisiteBundle\Repository\Language\LanguageRepository;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;

/*
 * Repositories. Doctrine requires the FQCN as service id: `gingerminds_multisite.repository.*` are aliases.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(SiteRepository::class)
        ->args([
            service('doctrine'),
            param('gingerminds_multisite.resource.site.entity'),
            service('gingerminds_multisite.security.credentials_encryptor'),
        ])
        ->call('setFilterHandlerRegistry', [service('gingerminds_core.filter_handler_registry')])
        ->tag('doctrine.repository_service');
    $services->alias('gingerminds_multisite.repository.site', SiteRepository::class);

    $services->set(LanguageRepository::class)
        ->args([service('doctrine'), param('gingerminds_multisite.resource.language.entity')])
        ->call('setFilterHandlerRegistry', [service('gingerminds_core.filter_handler_registry')])
        ->tag('doctrine.repository_service');
    $services->alias('gingerminds_multisite.repository.language', LanguageRepository::class);
};
