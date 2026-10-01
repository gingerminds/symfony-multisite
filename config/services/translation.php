<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\MultisiteBundle\ApiPlatform\State\TranslationProvider;
use Gingerminds\MultisiteBundle\Controller\Translation\TranslationRefreshController;
use Gingerminds\MultisiteBundle\MessageHandler\RefreshSiteTranslationsHandler;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Gingerminds\MultisiteBundle\Translation\GoogleDriveTranslationSource;
use Gingerminds\MultisiteBundle\Translation\TranslationFileParser;
use Gingerminds\MultisiteBundle\Translation\TranslationRefresher;
use Gingerminds\MultisiteBundle\Translation\TranslationService;
use Gingerminds\MultisiteBundle\Translation\TranslationSourceInterface;

/*
 * Front translations read from Google Drive: source, cache, API provider, refresh.
 * The logger channel is set by GingermindsMultisiteBundle::loadExtension().
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_multisite.translation.source', GoogleDriveTranslationSource::class);
    $services->alias(TranslationSourceInterface::class, 'gingerminds_multisite.translation.source');

    $services->set('gingerminds_multisite.translation.parser', TranslationFileParser::class);

    $services->set('gingerminds_multisite.translation.service', TranslationService::class)
        ->args([
            service('gingerminds_multisite.translation.source'),
            service('gingerminds_multisite.translation.parser'),
            service('gingerminds_multisite.security.credentials_encryptor'),
            service('gingerminds_multisite.translation_cache'),
            param('gingerminds_multisite.translation.enabled'),
            param('gingerminds_multisite.translation.cache_ttl'),
            service('logger')->ignoreOnInvalid(),
        ]);
    $services->alias(TranslationService::class, 'gingerminds_multisite.translation.service');

    $services->set('gingerminds_multisite.translation.refresh_handler', RefreshSiteTranslationsHandler::class)
        ->args([service(SiteRepository::class), service('gingerminds_multisite.translation.service')])
        ->tag('messenger.message_handler');

    $services->set('gingerminds_multisite.translation.refresher', TranslationRefresher::class)
        ->args([service('gingerminds_multisite.translation.refresh_handler'), service('messenger.default_bus')->nullOnInvalid()]);
    $services->alias(TranslationRefresher::class, 'gingerminds_multisite.translation.refresher');

    $services->set('gingerminds_multisite.api.provider.translation', TranslationProvider::class)
        ->args([
            service('gingerminds_multisite.translation.service'),
            service('gingerminds_multisite.context.site'),
            service(SiteRepository::class),
            service('request_stack'),
        ])
        ->tag('api_platform.state_provider');

    $services->set('gingerminds_multisite.controller.admin.translation_refresh', TranslationRefreshController::class)
        ->args([
            service(SiteRepository::class),
            service('gingerminds_multisite.translation.service'),
            service('gingerminds_multisite.translation.refresher'),
            service('security.authorization_checker'),
            service('security.csrf.token_manager'),
            service('router'),
            service('translator'),
        ])
        ->tag('controller.service_arguments')
        ->public();
};
