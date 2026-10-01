<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * On a TranslationInterface entity: its slug is unique per language and, when its
 * owner is site contexted, per site (the owner site, else the current one; the
 * shared rows count for every site). Laravel HandlesSlugUniquenessTrait.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class UniqueTranslationSlug extends Constraint
{
    public string $message = 'gingerminds_multisite.translation.slug_not_unique';

    public function __construct(
        public string $field = 'slug',
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
