<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

/**
 * An API resource whose response depends on the current language: its operations
 * document the `Accept-Language` header. Implied by TranslatableInterface and
 * LanguageContextedInterface; for the other resources (e.g. the front translations).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class LanguageScoped
{
}
