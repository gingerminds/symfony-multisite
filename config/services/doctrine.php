<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\MultisiteBundle\Doctrine\EventListener\SiteMetadataListener;

/*
 * Doctrine listeners.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_multisite.doctrine.site_metadata_listener', SiteMetadataListener::class)
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata']);
};
