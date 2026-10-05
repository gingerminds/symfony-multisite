<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

/**
 * An API resource whose response depends on the current language: its operations
 * document the `Accept-Language` header (e.g. every translatable or language
 * contexted entity, the front translations).
 */
interface LanguageScopedInterface
{
}
