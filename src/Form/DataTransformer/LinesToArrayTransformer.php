<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * A textarea edited one value per line, mapped to a list of trimmed non-empty strings.
 *
 * @implements DataTransformerInterface<list<string>|null, string>
 */
final class LinesToArrayTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        return \is_array($value) ? implode("\n", $value) : '';
    }

    /**
     * @return list<string>
     */
    public function reverseTransform(mixed $value): array
    {
        if (!\is_string($value)) {
            return [];
        }

        $lines = array_map(trim(...), preg_split('/\R/', $value) ?: []);

        return array_values(array_unique(array_filter($lines, static fn (string $line): bool => '' !== $line)));
    }
}
