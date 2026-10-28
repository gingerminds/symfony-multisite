<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\MultisiteBundle\Controller\Language\LanguageController;
use Gingerminds\MultisiteBundle\Controller\Site\SiteController;
use Gingerminds\MultisiteBundle\Controller\Site\SiteSwitchController;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Psr\Container\ContainerInterface;

/*
 * Admin controllers.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // AbstractCrudController: an AbstractController reading its services from its locator.
    foreach (['site' => SiteController::class, 'language' => LanguageController::class] as $name => $class) {
        $services->set('gingerminds_multisite.controller.admin.' . $name, $class)
            ->public()
            ->tag('controller.service_arguments')
            ->tag('container.service_subscriber')
            ->call('setContainer', [service(ContainerInterface::class)]);
        $services->alias($class, 'gingerminds_multisite.controller.admin.' . $name)->public();
    }

    $services->get('gingerminds_multisite.controller.admin.site')
        ->args([param('gingerminds_multisite.translation.enabled')]);

    $services->set('gingerminds_multisite.controller.admin.site_switch', SiteSwitchController::class)
        ->args([service(SiteRepository::class), service('security.csrf.token_manager'), service('router')])
        ->tag('controller.service_arguments')
        ->public();
};
