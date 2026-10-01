<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Multisite and multi-language support on top of GingermindsCoreBundle.
 */
final class GingermindsMultisiteBundle extends AbstractBundle
{
    protected string $extensionAlias = 'gingerminds_multisite';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $parameters = $container->parameters();
        $parameters->set('gingerminds_multisite.translation.enabled', $config['translation']['enabled']);
        $parameters->set('gingerminds_multisite.translation.cache_ttl', $config['translation']['cache_ttl']);
        $parameters->set('gingerminds_multisite.translation.log_channel', $config['translation']['log_channel']);
        $parameters->set('gingerminds_multisite.translation.encryption_key', $config['translation']['encryption_key']);
    }
}
