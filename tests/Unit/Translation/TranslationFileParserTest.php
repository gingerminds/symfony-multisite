<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Unit\Translation;

use Gingerminds\MultisiteBundle\Exception\TranslationSourceException;
use Gingerminds\MultisiteBundle\Tests\Application\Translation\FakeTranslationSource;
use Gingerminds\MultisiteBundle\Translation\TranslationFileParser;
use PHPUnit\Framework\TestCase;

final class TranslationFileParserTest extends TestCase
{
    public function testParsesOneColumnPerLocale(): void
    {
        $path = FakeTranslationSource::write([
            ['Key', ' FR ', 'en', null],
            ['home.title', 'Accueil', 'Home', 'ignored'],
            [' cart.count ', 3, null, null],
            ['', 'no key', 'skipped', null],
        ]);

        try {
            self::assertSame([
                'fr' => ['home.title' => 'Accueil', 'cart.count' => '3'],
                'en' => ['home.title' => 'Home', 'cart.count' => ''],
            ], new TranslationFileParser()->parse($path));
        } finally {
            unlink($path);
        }
    }

    public function testRequiresAKeyColumn(): void
    {
        $path = FakeTranslationSource::write([['fr', 'en'], ['Bonjour', 'Hello']]);

        try {
            $this->expectException(TranslationSourceException::class);
            new TranslationFileParser()->parse($path);
        } finally {
            unlink($path);
        }
    }
}
