<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle;

use Gingerminds\CoreBundle\DependencyInjection\Compiler\OverriddenEntityPass;
use Gingerminds\MultisiteBundle\Controller\Language\LanguageController;
use Gingerminds\MultisiteBundle\Controller\Site\SiteController;
use Gingerminds\MultisiteBundle\Controller\Translation\TranslationRefreshController;
use Gingerminds\MultisiteBundle\DependencyInjection\Compiler\CacheContextResolverPass;
use Gingerminds\MultisiteBundle\Doctrine\Filter\LanguageFilter;
use Gingerminds\MultisiteBundle\Doctrine\Filter\SiteFilter;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\Site;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Form\Language\LanguageType;
use Gingerminds\MultisiteBundle\Form\Site\SiteType;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Multisite and multi-language support on top of GingermindsCoreBundle.
 */
final class GingermindsMultisiteBundle extends AbstractBundle
{
    /**
     * Admin resources of the bundle, registered as `gingerminds_core` resources.
     */
    public const array RESOURCES = [
        'site' => [
            'entity' => Site::class,
            'interface' => SiteInterface::class,
            'controller' => SiteController::class,
            'form' => SiteType::class,
            'path' => 'sites',
            'permission' => 'sites',
        ],
        'language' => [
            'entity' => Language::class,
            'interface' => LanguageInterface::class,
            'controller' => LanguageController::class,
            'form' => LanguageType::class,
            'path' => 'languages',
            'permission' => 'languages',
        ],
    ];

    public const string TRANSLATION_DOMAIN = 'GingermindsMultisite';

    protected string $extensionAlias = 'gingerminds_multisite';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CacheContextResolverPass());
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

        $container->services()->get('gingerminds_multisite.translation.service')
            ->tag('monolog.logger', ['channel' => $config['translation']['log_channel']]);

        foreach (self::RESOURCES as $name => $resource) {
            $entity = $this->resourceValue($config, $name, 'entity');
            $parameters->set('gingerminds_multisite.resource.' . $name . '.entity', $entity);

            if ($entity !== $resource['entity']) {
                OverriddenEntityPass::registerOverriddenEntity($builder, $resource['entity']);
            }
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $config = $this->resolveConfig($builder);
        $resources = [];
        $resolveTargetEntities = [];

        foreach (self::RESOURCES as $name => $resource) {
            $resolveTargetEntities[$resource['interface']] = $this->resourceValue($config, $name, 'entity');
            $resources[$name] = [
                'entity' => $this->resourceValue($config, $name, 'entity'),
                'controller' => $this->resourceValue($config, $name, 'controller'),
                'form' => $this->resourceValue($config, $name, 'form'),
                'path' => $resource['path'],
                'permission' => $resource['permission'],
                'route_prefix' => 'gingerminds_multisite_' . $name,
                'translation_prefix' => $name,
                'translation_domain' => self::TRANSLATION_DOMAIN,
                'template_prefix' => '@GingermindsMultisite/pages/' . $name,
            ];
        }

        $builder->prependExtensionConfig('doctrine', [
            'orm' => [
                'resolve_target_entities' => $resolveTargetEntities,
                'mappings' => [
                    'GingermindsMultisiteSite' => $this->mapping('Site'),
                    'GingermindsMultisiteLanguage' => $this->mapping('Language'),
                ],
                // Enabled per request by ContextFilterListener.
                'filters' => [
                    SiteFilter::NAME => ['class' => SiteFilter::class, 'enabled' => false],
                    LanguageFilter::NAME => ['class' => LanguageFilter::class, 'enabled' => false],
                ],
            ],
        ]);

        if ($builder->hasExtension('twig')) {
            $builder->prependExtensionConfig('twig', [
                'form_themes' => ['@GingermindsMultisite/form/translations_theme.html.twig'],
            ]);
        }

        $builder->prependExtensionConfig('framework', [
            'cache' => [
                'pools' => [
                    'gingerminds_multisite.translation_cache' => ['adapter' => 'cache.app'],
                ],
            ],
        ]);

        if ($builder->hasExtension('monolog')) {
            // Google API errors in their own file (Laravel `google` daily channel), unless configured otherwise.
            $channel = $this->logChannel($builder);
            $builder->prependExtensionConfig('monolog', [
                'channels' => [$channel],
                'handlers' => [
                    'gingerminds_multisite_translation' => [
                        'type' => 'rotating_file',
                        'path' => '%kernel.logs_dir%/' . $channel . '.log',
                        'level' => 'debug',
                        'max_files' => 14,
                        'channels' => [$channel],
                    ],
                ],
            ]);
        }

        // Prepended: the project configuration still overrides any key.
        $builder->prependExtensionConfig('gingerminds_core', [
            'resources' => $resources,
            'permissions' => [TranslationRefreshController::PERMISSION],
            'admin_includes' => [
                'sidebar_bottom' => ['@GingermindsMultisite/admin/_site_switcher.html.twig'],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function resourceValue(array $config, string $name, string $key): string
    {
        $value = $config['resources'][$name][$key] ?? null;

        return \is_string($value) && '' !== $value ? $value : self::RESOURCES[$name][$key];
    }

    /**
     * `translation.log_channel` of the project configuration (the last one wins), `google` by default.
     */
    private function logChannel(ContainerBuilder $builder): string
    {
        $channel = 'google';

        foreach ($builder->getExtensionConfig($this->extensionAlias) as $config) {
            $value = $config['translation']['log_channel'] ?? null;

            if (\is_string($value) && '' !== $value) {
                $channel = $value;
            }
        }

        return $channel;
    }

    /**
     * The bundle configuration, `resources` only, while prepending the other extensions.
     *
     * @return array<string, mixed>
     */
    private function resolveConfig(ContainerBuilder $builder): array
    {
        $extension = $this->getContainerExtension();
        $configuration = $extension instanceof ConfigurationExtensionInterface ? $extension->getConfiguration([], $builder) : null;

        if (!$configuration instanceof ConfigurationInterface) {
            throw new LogicException('The GingermindsMultisiteBundle configuration cannot be resolved.');
        }

        // Only the resources are needed here: the other keys may hold env placeholders
        // (e.g. `%env(bool:...)%`), not resolvable before the extensions are loaded.
        $configs = array_map(
            static fn (array $config): array => array_intersect_key($config, ['resources' => true]),
            $builder->getExtensionConfig($this->extensionAlias),
        );

        return new Processor()->processConfiguration($configuration, $builder->getParameterBag()->resolveValue($configs));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapping(string $directory): array
    {
        return [
            'type' => 'attribute',
            'is_bundle' => false,
            'dir' => $this->getPath() . '/src/Entity/' . $directory,
            'prefix' => 'Gingerminds\\MultisiteBundle\\Entity\\' . $directory,
        ];
    }
}
