<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\MultisiteBundle\Doctrine\EventListener\ContextEntityListener;
use Gingerminds\MultisiteBundle\Doctrine\EventListener\LanguageContextedMetadataListener;
use Gingerminds\MultisiteBundle\Doctrine\EventListener\SiteMetadataListener;
use Gingerminds\MultisiteBundle\Doctrine\EventListener\TranslatableMetadataListener;

/*
 * Doctrine listeners.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_multisite.doctrine.site_metadata_listener', SiteMetadataListener::class)
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata']);

    $services->set('gingerminds_multisite.doctrine.translatable_metadata_listener', TranslatableMetadataListener::class)
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata']);

    $services->set('gingerminds_multisite.doctrine.language_contexted_metadata_listener', LanguageContextedMetadataListener::class)
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata']);

    $services->set('gingerminds_multisite.doctrine.context_entity_listener', ContextEntityListener::class)
        ->args([service('gingerminds_multisite.context.site'), service('gingerminds_multisite.context.language')])
        ->tag('doctrine.event_listener', ['event' => 'postLoad'])
        ->tag('doctrine.event_listener', ['event' => 'prePersist']);
};
