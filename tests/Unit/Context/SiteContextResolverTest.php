<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Unit\Context;

use Gingerminds\MultisiteBundle\Context\SiteContextResolver;
use Gingerminds\MultisiteBundle\Entity\Site\Site;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Gingerminds\MultisiteBundle\Security\SiteAccess;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SiteContextResolverTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function unknownHosts(): iterable
    {
        yield 'first_site, front' => [SiteContextResolver::UNKNOWN_HOST_FIRST_SITE, '/', false];
        yield 'first_site, API' => [SiteContextResolver::UNKNOWN_HOST_FIRST_SITE, '/api/translations', false];
        yield 'not_found, front' => [SiteContextResolver::UNKNOWN_HOST_NOT_FOUND, '/', true];
        yield 'not_found, API' => [SiteContextResolver::UNKNOWN_HOST_NOT_FOUND, '/api/translations', true];
        yield 'not_found, admin' => [SiteContextResolver::UNKNOWN_HOST_NOT_FOUND, '/admin/sites', false];
        yield 'not_found, admin root' => [SiteContextResolver::UNKNOWN_HOST_NOT_FOUND, '/admin', false];
        yield 'not_found, path starting like the admin' => [SiteContextResolver::UNKNOWN_HOST_NOT_FOUND, '/administration', true];
    }

    #[DataProvider('unknownHosts')]
    public function testUnknownHost(string $unknownHost, string $path, bool $notFound): void
    {
        $first = new Site();
        $resolver = new SiteContextResolver($this->sites(host: null, first: $first), $this->access(), $unknownHost, 'admin');

        if ($notFound) {
            $this->expectException(NotFoundHttpException::class);
        }

        self::assertSame($first, $resolver->resolve(Request::create('https://unknown.example.com' . $path)));
    }

    public function testAKnownHostIsNeverNotFound(): void
    {
        $site = new Site();
        $resolver = new SiteContextResolver($this->sites(host: $site, first: new Site()), $this->access(), SiteContextResolver::UNKNOWN_HOST_NOT_FOUND, 'admin');

        self::assertSame($site, $resolver->resolve(Request::create('https://alpha.example.com/')));
    }

    private function sites(?Site $host, Site $first): SiteRepository
    {
        $sites = $this->createStub(SiteRepository::class);
        $sites->method('findOneByHost')->willReturn($host);
        $sites->method('findFirst')->willReturn($first);

        return $sites;
    }

    private function access(): SiteAccess
    {
        // No user: every site.
        return new SiteAccess($this->createStub(Security::class));
    }
}
