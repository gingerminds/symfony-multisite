<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Unit\Context;

use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\LanguageContextResolver;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class LanguageContextTest extends TestCase
{
    /**
     * @return iterable<string, array{bool, string|null, list<int>}>
     */
    public static function requests(): iterable
    {
        yield 'fallback, API' => [true, '/api/menus', [1, 2]];
        yield 'fallback, admin' => [true, '/admin/menus', [1, 2]];
        yield 'no fallback, API' => [false, '/api/menus', [1]];
        yield 'no fallback, API root' => [false, '/api', [1]];
        yield 'no fallback, admin' => [false, '/admin/menus', [1, 2]];
        yield 'no fallback, path starting like the API' => [false, '/apiculture', [1, 2]];
        yield 'no fallback, no request (command)' => [false, null, [1, 2]];
    }

    /**
     * @param list<int> $expected
     */
    #[DataProvider('requests')]
    public function testTheFallbackLanguageIsTriedUnlessDisabledForTheApi(bool $apiFallback, ?string $path, array $expected): void
    {
        $requestStack = new RequestStack();

        if (null !== $path) {
            $requestStack->push(Request::create($path));
        }

        $context = new LanguageContext($this->createStub(SiteContext::class), new LanguageContextResolver(), $requestStack, $apiFallback, 'api');
        $context->setLanguages($this->language(1, 'en'), $this->language(2, 'fr'));

        self::assertSame($expected, $context->ids());
        self::assertSame(2 === \count($expected), $context->fallsBack());
    }

    private function language(int $id, string $iso): Language
    {
        $language = new Language();
        $language->setIso($iso);
        new \ReflectionProperty($language, 'id')->setValue($language, $id);

        return $language;
    }
}
