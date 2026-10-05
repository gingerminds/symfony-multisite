<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

/**
 * An API resource whose response depends on the current site: its operations
 * document the `X-Site-Id` header (e.g. every SiteContextedInterface entity, the
 * front translations).
 */
interface SiteScopedInterface
{
}
