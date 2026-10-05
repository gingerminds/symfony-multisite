<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Translation;

use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Message\RefreshSiteTranslations;
use Gingerminds\MultisiteBundle\MessageHandler\RefreshSiteTranslationsHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Refreshes the front translations cache of a site: through Messenger when it is
 * installed (asynchronous when a transport routes RefreshSiteTranslations), right
 * away otherwise.
 */
final readonly class TranslationRefresher
{
    public function __construct(
        private RefreshSiteTranslationsHandler $handler,
        private ?MessageBusInterface $bus = null,
    ) {
    }

    public function refresh(SiteInterface $site): void
    {
        $message = new RefreshSiteTranslations((int) $site->getId());

        if ($this->bus instanceof MessageBusInterface) {
            $this->bus->dispatch($message);

            return;
        }

        ($this->handler)($message);
    }
}
