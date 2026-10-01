<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Message;

/**
 * Re-downloads the front translations of a site into the cache
 * (RefreshSiteTranslationsHandler), asynchronously when a transport routes it.
 */
final readonly class RefreshSiteTranslations
{
    public function __construct(
        public int $siteId,
    ) {
    }
}
