<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Doctrine\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Gingerminds\MultisiteBundle\Entity\Site\SiteFrontUrl;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteLanguage;

final class SiteMetadataListener
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $metadata = $args->getClassMetadata();

        if ($metadata->isMappedSuperclass || !is_a($metadata->getName(), SiteInterface::class, true)) {
            return;
        }

        if (!$metadata->hasAssociation('siteLanguages')) {
            $metadata->mapOneToMany([
                'fieldName' => 'siteLanguages',
                'targetEntity' => SiteLanguage::class,
                'mappedBy' => 'site',
                'cascade' => ['persist', 'remove'],
                'orphanRemoval' => true,
                'orderBy' => ['id' => 'ASC'],
            ]);
        }

        if (!$metadata->hasAssociation('frontUrls')) {
            $metadata->mapOneToMany([
                'fieldName' => 'frontUrls',
                'targetEntity' => SiteFrontUrl::class,
                'mappedBy' => 'site',
                'cascade' => ['persist', 'remove'],
                'orphanRemoval' => true,
                'orderBy' => ['url' => 'ASC'],
            ]);
        }
    }
}
