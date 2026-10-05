<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\MessageHandler;

use Gingerminds\MultisiteBundle\Message\RefreshSiteTranslations;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;
use Gingerminds\MultisiteBundle\Translation\TranslationService;

final readonly class RefreshSiteTranslationsHandler
{
    public function __construct(
        private SiteRepository $sites,
        private TranslationService $translations,
    ) {
    }

    public function __invoke(RefreshSiteTranslations $message): void
    {
        $site = $this->sites->find($message->siteId);

        if (null === $site) {
            return;
        }

        $this->translations->resetCacheForSite($site);
        $this->translations->getTranslationsForSite($site);
    }
}
