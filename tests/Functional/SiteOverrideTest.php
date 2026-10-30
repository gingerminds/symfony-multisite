<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional;

use ApiPlatform\Metadata\NotExposed;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gingerminds\CoreBundle\Resource\ResourceRegistry;
use Gingerminds\MultisiteBundle\Entity\Site\Site as BundleSite;
use Gingerminds\MultisiteBundle\Entity\Site\SiteFrontUrl;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteLanguage;
use Gingerminds\MultisiteBundle\Tests\Application\Kernel;
use Gingerminds\MultisiteBundle\Tests\Application\Override\Site;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class SiteOverrideTest extends TestCase
{
    private static Kernel $kernel;

    public static function setUpBeforeClass(): void
    {
        self::$kernel = new Kernel('override', true);
        new Filesystem()->remove(self::$kernel->getCacheDir());
        self::$kernel->boot();
    }

    public static function tearDownAfterClass(): void
    {
        self::$kernel->shutdown();
    }

    public function testOnlyTheProjectEntityIsMapped(): void
    {
        $entityManager = $this->entityManager();
        $entities = array_map(
            static fn (ClassMetadata $metadata): string => $metadata->getName(),
            array_filter($entityManager->getMetadataFactory()->getAllMetadata(), static fn (ClassMetadata $metadata): bool => !$metadata->isMappedSuperclass),
        );

        self::assertContains(Site::class, $entities);
        self::assertNotContains(BundleSite::class, $entities);
        self::assertSame('sites', $entityManager->getClassMetadata(Site::class)->getTableName());
        self::assertTrue($entityManager->getClassMetadata(Site::class)->hasField('brand'));
    }

    public function testRelationsTargetTheProjectEntity(): void
    {
        $entityManager = $this->entityManager();
        $metadata = $entityManager->getClassMetadata(Site::class);

        // Inverse sides mapped by SiteMetadataListener on the project entity.
        self::assertSame(SiteLanguage::class, $metadata->getAssociationTargetClass('siteLanguages'));
        self::assertSame(SiteFrontUrl::class, $metadata->getAssociationTargetClass('frontUrls'));
        self::assertSame(Site::class, $entityManager->getClassMetadata(SiteLanguage::class)->getAssociationTargetClass('site'));
        self::assertSame(Site::class, $entityManager->getClassMetadata(SiteInterface::class)->getName());
        self::assertSame(Site::class, self::$kernel->getContainer()->get('test.service_container')->get(ResourceRegistry::class)->getEntityClass('site'));
    }

    public function testTheProjectEntityInheritsTheBundleApiResource(): void
    {
        $container = self::$kernel->getContainer()->get('test.service_container');
        $resources = iterator_to_array($container->get('api_platform.metadata.resource.name_collection_factory')->create());

        self::assertContains(Site::class, $resources);
        self::assertNotContains(BundleSite::class, $resources);

        $operations = [];

        foreach ($container->get('api_platform.metadata.resource.metadata_collection_factory')->create(Site::class) as $resource) {
            foreach ($resource->getOperations() ?? [] as $operation) {
                if (!$operation instanceof NotExposed) {
                    $operations[] = $operation->getUriTemplate();
                }
            }
        }

        self::assertSame(['/sites{._format}'], $operations);
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::$kernel->getContainer()->get('doctrine')->getManager();
    }
}
