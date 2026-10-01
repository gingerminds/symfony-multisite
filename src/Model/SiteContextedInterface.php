<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

/**
 * An entity of one site (`site_id`), or shared by every site (no site).
 *
 * The `gingerminds_site` Doctrine filter restricts its queries to the current
 * site and the shared rows, and a new entity without site gets the current one
 * (SiteContextedTrait maps the relation).
 */
interface SiteContextedInterface
{
    public function getSite(): ?SiteInterface;

    public function setSite(?SiteInterface $site): void;
}
