<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Unit\Http;

use Gingerminds\MultisiteBundle\Http\RefererUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RefererUrlTest extends TestCase
{
    #[DataProvider('referers')]
    public function testSameHostOnly(?string $referer, string $expected): void
    {
        $request = Request::create('https://admin.example.com/admin/site-switch', 'POST');

        if (null !== $referer) {
            $request->headers->set('referer', $referer);
        }

        self::assertSame($expected, RefererUrl::sameHost($request, '/admin/'));
    }

    /**
     * @return iterable<string, array{string|null, string}>
     */
    public static function referers(): iterable
    {
        yield 'same host, with query' => ['https://admin.example.com/admin/sites?page=2', '/admin/sites?page=2'];
        yield 'another host' => ['https://evil.example.com/phishing', '/admin/'];
        yield 'protocol relative' => ['//evil.example.com/admin', '/admin/'];
        yield 'no path' => ['https://admin.example.com', '/admin/'];
        yield 'no referer' => [null, '/admin/'];
        yield 'garbage' => ['http:///', '/admin/'];
    }
}
