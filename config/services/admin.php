<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\MultisiteBundle\Menu\MultisiteAdminMenuProvider;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Gingerminds\MultisiteBundle\Twig\MultisiteExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_multisite.admin_menu.provider', MultisiteAdminMenuProvider::class)
        ->args([service('gingerminds_core.resource_registry')])
        ->tag('gingerminds_core.admin_menu_provider');

    $services->set('gingerminds_multisite.twig.extension', MultisiteExtension::class)
        ->args([
            service(SiteRepository::class),
            service('gingerminds_multisite.context.site'),
            service('gingerminds_multisite.context.language'),
        ])
        ->tag('twig.extension')
        ->tag('kernel.reset', ['method' => 'reset']);
};
