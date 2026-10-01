<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The test application boots with the core and the multisite bundles.
 */
final class BundleBootTest extends ApiTestCase
{
    public function testBundleIsRegisteredWithItsConfiguration(): void
    {
        /** @var KernelInterface $kernel */
        $kernel = self::getContainer()->get('kernel');

        self::assertArrayHasKey('GingermindsMultisiteBundle', $kernel->getBundles());
        self::assertTrue(self::getContainer()->getParameter('gingerminds_multisite.translation.enabled'));
        self::assertSame(300, self::getContainer()->getParameter('gingerminds_multisite.translation.cache_ttl'));
        self::assertSame('test-secret', self::getContainer()->getParameter('gingerminds_multisite.translation.encryption_key'));
    }

    public function testAdminDashboardRenders(): void
    {
        $this->client->loginUser($this->fixtures->user('admin@example.com', superAdmin: true), 'admin');
        $this->client->request('GET', '/admin/');

        self::assertResponseIsSuccessful();
    }
}
