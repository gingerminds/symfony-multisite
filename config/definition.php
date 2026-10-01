<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $resource = static function (NodeBuilder $children, string $name): void {
        $children
            ->arrayNode($name)
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('entity')
                        ->info('Entity class, extending the bundle Base' . ucfirst($name) . ' (null: the bundle one).')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('controller')->defaultNull()->end()
                    ->scalarNode('form')->defaultNull()->end()
                ->end()
            ->end();
    };

    $children = $definition->rootNode()->children();

    $resources = $children->arrayNode('resources')
        ->info('Overridable resources of the bundle, registered as gingerminds_core resources.')
        ->addDefaultsIfNotSet()
        ->children();
    $resource($resources, 'site');
    $resource($resources, 'language');
    $resources->end()->end();

    $children
        ->arrayNode('translation')
            ->info('Front translations read from a per-site Google Drive xlsx file.')
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')
                    ->info('Master switch of the feature (API endpoint, admin fields and refresh).')
                    ->defaultFalse()
                ->end()
                ->integerNode('cache_ttl')
                    ->info('Seconds the parsed translations of a site are cached (protects the Google API quota).')
                    ->defaultValue(300)
                    ->min(0)
                ->end()
                ->scalarNode('log_channel')
                    ->info('Monolog channel of the Google API errors.')
                    ->defaultValue('google')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('encryption_key')
                    ->info('Secret encrypting the Google service account credentials at rest. Changing it makes the stored credentials unreadable.')
                    ->defaultValue('%kernel.secret%')
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ->end();
};
