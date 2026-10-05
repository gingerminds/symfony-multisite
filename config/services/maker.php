<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CoreBundle\Maker\Extension\ResourceMakerExtensionInterface;
use Gingerminds\MultisiteBundle\Maker\TranslatedResourceMakerExtension;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('gingerminds_multisite.maker.translated_extension', TranslatedResourceMakerExtension::class)
        ->tag(ResourceMakerExtensionInterface::TAG);
};
