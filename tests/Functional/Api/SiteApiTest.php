<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional\Api;

use Gingerminds\CoreBundle\ApiPlatform\Metadata\HeaderParameterRegistry;
use Gingerminds\MultisiteBundle\ApiResource\Translation;
use Gingerminds\MultisiteBundle\Entity\Site\Site;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Article;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Media;
use Gingerminds\MultisiteBundle\Tests\Functional\ApiTestCase;

final class SiteApiTest extends ApiTestCase
{
    public function testSitesArePublicWithSnakeCaseFields(): void
    {
        $fr = $this->fixtures->language('fr', 'Français');
        $en = $this->fixtures->language('en', 'English');
        $site = $this->fixtures->site('alpha', [$fr, $en], $en, 'https://alpha.example.com');
        $site->setFrontUrlValues(['https://www.alpha.example.com']);
        $site->setGoogleDriveFileId('drive-file');
        $site->setEncryptedGoogleCredentials('v1:secret');
        self::getContainer()->get('doctrine')->getManager()->flush();

        $data = $this->api('GET', '/api/sites');

        $this->assertStatus(200);
        self::assertSame(1, $data['totalItems']);
        $item = $data['member'][0];
        self::assertSame(['@id', '@type', 'id', 'code', 'url', 'front_urls', 'languages', 'default_language'], array_keys($item));
        self::assertSame('alpha', $item['code']);
        self::assertSame(['https://www.alpha.example.com'], $item['front_urls']);
        self::assertSame([['fr', 'Français'], ['en', 'English']], array_map(static fn (array $language): array => [$language['iso'], $language['label']], $item['languages']));
        self::assertSame('en', $item['default_language']['iso']);
        self::assertStringNotContainsString('secret', (string) $this->client->getResponse()->getContent());
        self::assertStringNotContainsString('drive-file', (string) $this->client->getResponse()->getContent());
    }

    public function testPaginationAndSort(): void
    {
        foreach (['charlie', 'alpha', 'bravo'] as $code) {
            $this->fixtures->site($code);
        }

        $data = $this->api('GET', '/api/sites?itemsPerPage=2&sortBy=code&sort=asc');

        $this->assertStatus(200);
        self::assertSame(3, $data['totalItems']);
        self::assertSame(['alpha', 'bravo'], array_column($data['member'], 'code'));
    }

    public function testNoSiteWithoutDefaultLanguage(): void
    {
        $this->fixtures->site('bare');

        $item = $this->api('GET', '/api/sites')['member'][0];

        self::assertNull($item['default_language']);
        self::assertSame([], $item['languages']);
        self::assertSame([], $item['front_urls']);
    }

    public function testTheCachedResponseVariesWithTheCurrentSite(): void
    {
        $this->fixtures->site('alpha');
        $beta = $this->fixtures->site('beta');

        $this->api('GET', '/api/sites');
        self::assertSame('MISS', $this->client->getResponse()->headers->get('X-Gingerminds-Cache'));

        $this->api('GET', '/api/sites');
        self::assertSame('HIT', $this->client->getResponse()->headers->get('X-Gingerminds-Cache'));

        $this->client->request('GET', '/api/sites', server: ['HTTP_ACCEPT' => 'application/ld+json', 'HTTP_X_SITE_ID' => (string) $beta->getId()]);
        self::assertSame('MISS', $this->client->getResponse()->headers->get('X-Gingerminds-Cache'), 'Another site, another cache entry.');
    }

    public function testTheAdminSessionIsIgnoredByTheStatelessApi(): void
    {
        $this->fixtures->site('alpha');
        $beta = $this->fixtures->site('beta');
        // Swagger UI in the browser of an admin: the session cookie (and its site switcher choice) is sent.
        $this->client->loginUser($this->fixtures->user('swagger@example.com', superAdmin: true), 'admin');
        $crawler = $this->client->request('GET', '/admin/');
        $this->client->submit($crawler->filter('.gm-site-switcher button[value="' . $beta->getId() . '"]')->form());

        $data = $this->api('GET', '/api/sites');

        $this->assertStatus(200);
        self::assertSame(2, $data['totalItems']);
    }

    public function testLanguagesAreNotAnApiResource(): void
    {
        $this->api('GET', '/api/languages');

        $this->assertStatus(404);
    }

    public function testContextHeadersAreDocumentedOnTheContextedResources(): void
    {
        $registry = self::getContainer()->get(HeaderParameterRegistry::class);
        $keys = static fn (string $class): array => array_values(array_unique(array_map(static fn ($parameter): string => (string) $parameter->getKey(), $registry->for($class))));

        self::assertSame(['X-Site-Id', 'Accept-Language'], $keys(Article::class));
        self::assertSame(['Accept-Language'], $keys(Media::class));
        self::assertSame(['X-Site-Id', 'Accept-Language'], $keys(Translation::class));
        self::assertSame([], $keys(Site::class), 'The sites list itself does not depend on the current site.');
    }

    public function testTheOpenApiDocumentationListsTheContextHeaders(): void
    {
        $this->client->request('GET', '/api/docs', server: ['HTTP_ACCEPT' => 'application/vnd.openapi+json']);
        self::assertResponseIsSuccessful();
        $doc = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        $headers = array_column(array_filter($doc['paths']['/api/translations']['get']['parameters'] ?? [], static fn (array $parameter): bool => 'header' === $parameter['in']), 'name');

        self::assertSame(['X-Site-Id', 'Accept-Language'], $headers);
    }
}
