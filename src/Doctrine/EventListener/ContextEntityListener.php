<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Doctrine\EventListener;

use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;

/**
 * Gives the TranslatableInterface entities the current languages (lazily resolved),
 * and the current site to a new SiteContextedInterface entity without site.
 */
final readonly class ContextEntityListener
{
    public function __construct(
        private SiteContext $siteContext,
        private LanguageContext $languageContext,
    ) {
    }

    public function postLoad(PostLoadEventArgs $args): void
    {
        $this->setTranslationLanguages($args->getObject());
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->setTranslationLanguages($entity);

        if ($entity instanceof SiteContextedInterface && !$entity->getSite() instanceof SiteInterface && $this->siteContext->has()) {
            $entity->setSite($this->siteContext->site());
        }
    }

    private function setTranslationLanguages(object $entity): void
    {
        if ($entity instanceof TranslatableInterface) {
            $entity->setTranslationLanguages($this->languageContext->ids(...));
        }
    }
}
