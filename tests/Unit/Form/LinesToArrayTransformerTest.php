<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Unit\Form;

use Gingerminds\MultisiteBundle\Form\DataTransformer\LinesToArrayTransformer;
use PHPUnit\Framework\TestCase;

final class LinesToArrayTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new LinesToArrayTransformer();

        self::assertSame("a\nb", $transformer->transform(['a', 'b']));
        self::assertSame('', $transformer->transform(null));
    }

    public function testReverseTransformTrimsAndDeduplicates(): void
    {
        $transformer = new LinesToArrayTransformer();

        self::assertSame(['a', 'b'], $transformer->reverseTransform(" a \r\n\r\nb\na\n"));
        self::assertSame([], $transformer->reverseTransform(null));
    }
}
