<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\MultisiteBundle\Controller\Language\LanguageController;
use Gingerminds\MultisiteBundle\Controller\Site\SiteController;
use Gingerminds\MultisiteBundle\Controller\Site\SiteSwitchController;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;

/*
 * Admin controllers.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_multisite.controller.admin.site', SiteController::class)
        ->args([service('gingerminds_core.controller.context'), param('gingerminds_multisite.translation.enabled')])
        ->tag('controller.service_arguments');
    $services->alias(SiteController::class, 'gingerminds_multisite.controller.admin.site')->public();

    $services->set('gingerminds_multisite.controller.admin.language', LanguageController::class)
        ->args([service('gingerminds_core.controller.context')])
        ->tag('controller.service_arguments');
    $services->alias(LanguageController::class, 'gingerminds_multisite.controller.admin.language')->public();

    $services->set('gingerminds_multisite.controller.admin.site_switch', SiteSwitchController::class)
        ->args([service(SiteRepository::class), service('security.csrf.token_manager'), service('router')])
        ->tag('controller.service_arguments')
        ->public();
};
