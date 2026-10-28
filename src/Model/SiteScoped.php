<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

/**
 * An API resource whose response depends on the current site: its operations
 * document the `X-Site-Id` header. Implied by SiteContextedInterface; for the
 * other resources (e.g. the front translations).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class SiteScoped
{
}
