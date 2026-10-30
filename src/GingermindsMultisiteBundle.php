<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle;

use Gingerminds\CoreBundle\Bundle\AbstractResourceBundle;
use Gingerminds\MultisiteBundle\Controller\Language\LanguageController;
use Gingerminds\MultisiteBundle\Controller\Site\SiteController;
use Gingerminds\MultisiteBundle\Controller\Translation\TranslationRefreshController;
use Gingerminds\MultisiteBundle\Doctrine\Filter\LanguageFilter;
use Gingerminds\MultisiteBundle\Doctrine\Filter\SiteFilter;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\Site;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Form\Language\LanguageType;
use Gingerminds\MultisiteBundle\Form\Site\SiteType;
use Gingerminds\MultisiteBundle\Security\SiteAccess;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Multisite and multi-language support on top of GingermindsCoreBundle.
 */
final class GingermindsMultisiteBundle extends AbstractResourceBundle
{
    /**
     * Overridable entities and admin resources of the bundle (AbstractResourceBundle::resources()).
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

        // make:gm:* `--translated` option (dev only dependency).
        if (class_exists(MakerBundle::class)) {
            $container->import('../config/services/maker.php');
        }

        $parameters = $container->parameters();
        $parameters->set('gingerminds_multisite.translation.enabled', $config['translation']['enabled']);
        $parameters->set('gingerminds_multisite.translation.cache_ttl', $config['translation']['cache_ttl']);
        $parameters->set('gingerminds_multisite.translation.log_channel', $config['translation']['log_channel']);
        $parameters->set('gingerminds_multisite.translation.encryption_key', $config['translation']['encryption_key']);
        $parameters->set('gingerminds_multisite.translation_fallback', $config['translation_fallback']);

        $container->services()->get('gingerminds_multisite.translation.service')
            ->tag('monolog.logger', ['channel' => $config['translation']['log_channel']]);

        parent::loadExtension($config, $container, $builder);
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::prependExtension($container, $builder);

        $builder->prependExtensionConfig('doctrine', [
            'orm' => [
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
            $channel = (string) self::extensionConfigValue($builder, $this->extensionAlias, 'translation.log_channel', 'google');
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
            'permissions' => [TranslationRefreshController::PERMISSION, SiteAccess::ASSIGN_PERMISSION],
            'admin_includes' => [
                'sidebar_bottom' => ['@GingermindsMultisite/admin/_site_switcher.html.twig'],
                'user_form_sidebar' => ['@GingermindsMultisite/admin/_user_sites.html.twig'],
            ],
        ]);
    }

    protected function resources(array $config): array
    {
        return self::RESOURCES;
    }

    protected function entityDirectories(): array
    {
        return ['Site', 'Language'];
    }
}
