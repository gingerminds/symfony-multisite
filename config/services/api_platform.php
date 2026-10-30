<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\MultisiteBundle\ApiPlatform\Metadata\ContextHeaderParametersResourceMetadataCollectionFactory;

/*
 * Documented context headers of the API operations.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_multisite.api.metadata.context_header_parameters', ContextHeaderParametersResourceMetadataCollectionFactory::class)
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 0)
        ->args([service('.inner')]);
};
