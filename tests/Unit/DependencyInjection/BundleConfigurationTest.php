<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Unit\DependencyInjection;

use Gingerminds\CoreBundle\DependencyInjection\Compiler\OverriddenEntityPass;
use Gingerminds\MultisiteBundle\Controller\Site\SiteController;
use Gingerminds\MultisiteBundle\Doctrine\Filter\LanguageFilter;
use Gingerminds\MultisiteBundle\Doctrine\Filter\SiteFilter;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\Site;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\GingermindsMultisiteBundle;
use Gingerminds\MultisiteBundle\Tests\Application\Override\Site as ProjectSite;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

/**
 * What the bundle prepends to the other extensions and loads, without booting a kernel.
 */
final class BundleConfigurationTest extends TestCase
{
    public function testDefaultsArePrependedToDoctrineAndTheCore(): void
    {
        $container = $this->prepend([]);

        $doctrine = $this->merged($container, 'doctrine')['orm'];
        self::assertSame([SiteInterface::class => Site::class, LanguageInterface::class => Language::class], $doctrine['resolve_target_entities']);
        self::assertSame(['GingermindsMultisiteSite', 'GingermindsMultisiteLanguage'], array_keys($doctrine['mappings']));
        self::assertSame(['class' => SiteFilter::class, 'enabled' => false], $doctrine['filters'][SiteFilter::NAME]);
        self::assertSame(['class' => LanguageFilter::class, 'enabled' => false], $doctrine['filters'][LanguageFilter::NAME]);

        $core = $this->merged($container, 'gingerminds_core');
        self::assertSame([
            'entity' => Site::class,
            'controller' => SiteController::class,
            'form' => $core['resources']['site']['form'],
            'path' => 'sites',
            'permission' => 'sites',
            'route_prefix' => 'gingerminds_multisite_site',
            'translation_prefix' => 'site',
            'translation_domain' => 'GingermindsMultisite',
            'template_prefix' => '@GingermindsMultisite/pages/site',
        ], $core['resources']['site']);
        self::assertSame(['manage translations'], $core['permissions']);
        self::assertSame(['sidebar_bottom' => ['@GingermindsMultisite/admin/_site_switcher.html.twig']], $core['admin_includes']);
        self::assertArrayHasKey('gingerminds_multisite.translation_cache', $this->merged($container, 'framework')['cache']['pools']);
    }

    public function testTwigAndMonologOnlyWhenRegistered(): void
    {
        $without = $this->prepend([]);
        self::assertSame([], $without->getExtensionConfig('twig'));
        self::assertSame([], $without->getExtensionConfig('monolog'));

        $with = $this->prepend([['translation' => ['log_channel' => 'drive']]], ['twig', 'monolog']);
        self::assertSame(['@GingermindsMultisite/form/translations_theme.html.twig'], $this->merged($with, 'twig')['form_themes']);
        $monolog = $this->merged($with, 'monolog');
        self::assertSame(['drive'], $monolog['channels']);
        self::assertSame('%kernel.logs_dir%/drive.log', $monolog['handlers']['gingerminds_multisite_translation']['path']);
    }

    public function testAProjectEntityOverridesTheBundleOne(): void
    {
        $configs = [['resources' => ['site' => ['entity' => ProjectSite::class]]]];
        $container = $this->prepend($configs);

        self::assertSame(ProjectSite::class, $this->merged($container, 'doctrine')['orm']['resolve_target_entities'][SiteInterface::class]);
        self::assertSame(ProjectSite::class, $this->merged($container, 'gingerminds_core')['resources']['site']['entity']);

        $this->bundle()->getContainerExtension()?->load($configs, $container);

        self::assertSame(ProjectSite::class, $container->getParameter('gingerminds_multisite.resource.site.entity'));
        self::assertSame(Language::class, $container->getParameter('gingerminds_multisite.resource.language.entity'));
        $overridden = array_merge(...array_values(array_map(static fn (array $tags): array => array_column($tags, 'class'), $container->findTaggedServiceIds(OverriddenEntityPass::TAG))));
        self::assertSame([Site::class], $overridden);
    }

    public function testAnEnvPlaceholderDoesNotBreakThePrepend(): void
    {
        $container = $this->prepend([['translation' => ['enabled' => '%env(bool:TRANSLATION_ENABLED)%']]]);

        self::assertSame(Site::class, $this->merged($container, 'gingerminds_core')['resources']['site']['entity']);
    }

    /**
     * @param list<array<string, mixed>> $configs    gingerminds_multisite configurations
     * @param list<string>               $extensions other registered extensions
     */
    private function prepend(array $configs, array $extensions = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.secret', 'secret');
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());
        $container->setParameter('kernel.bundles_metadata', []);

        foreach ($extensions as $alias) {
            $container->registerExtension(new class($alias) extends Extension {
                public function __construct(private readonly string $alias)
                {
                }

                public function load(array $configs, ContainerBuilder $container): void
                {
                }

                public function getAlias(): string
                {
                    return $this->alias;
                }
            });
        }

        $extension = $this->bundle()->getContainerExtension();
        self::assertInstanceOf(ExtensionInterface::class, $extension);
        self::assertInstanceOf(PrependExtensionInterface::class, $extension);
        $container->registerExtension($extension);

        foreach ($configs as $config) {
            $container->loadFromExtension('gingerminds_multisite', $config);
        }

        $extension->prepend($container);

        return $container;
    }

    /**
     * @return array<string, mixed>
     */
    private function merged(ContainerBuilder $container, string $extension): array
    {
        return array_replace_recursive([], ...$container->getExtensionConfig($extension));
    }

    private function bundle(): GingermindsMultisiteBundle
    {
        return new GingermindsMultisiteBundle();
    }
}
