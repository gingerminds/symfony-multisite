<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Unit\Context;

use Gingerminds\MultisiteBundle\Context\AcceptLanguage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AcceptLanguageTest extends TestCase
{
    /**
     * @param list<string> $expected
     */
    #[DataProvider('headers')]
    public function testParse(?string $header, array $expected): void
    {
        self::assertSame($expected, AcceptLanguage::parse($header));
        self::assertSame($expected[0] ?? null, AcceptLanguage::primary($header));
    }

    /**
     * @return iterable<string, array{string|null, list<string>}>
     */
    public static function headers(): iterable
    {
        yield 'none' => [null, []];
        yield 'empty' => ['  ', []];
        yield 'single' => ['fr', ['fr']];
        yield 'region and quality' => ['fr-FR,en;q=0.8', ['fr', 'en']];
        yield 'header order, no duplicates' => ['en-GB, fr;q=0.9, en-US;q=0.5', ['en', 'fr']];
        yield 'case, underscore and wildcard' => ['DE_ch, *;q=0.1', ['de']];
    }
}
