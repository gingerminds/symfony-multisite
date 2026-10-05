<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Model\LanguageContextedInterface;
use Gingerminds\MultisiteBundle\Model\LanguageContextedTrait;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedTrait;

/**
 * Overrides documented in docs/Entities.md: the Laravel `language_document`
 * join table name, a site deletion keeping the document.
 */
#[ORM\Entity]
#[ORM\Table(name: 'documents')]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(name: 'site', joinColumns: [new ORM\JoinColumn(name: 'site_id', onDelete: 'SET NULL')]),
    new ORM\AssociationOverride(name: 'languages', joinTable: new ORM\JoinTable(name: 'language_document')),
])]
class Document implements SiteContextedInterface, LanguageContextedInterface
{
    use LanguageContextedTrait;
    use SiteContextedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
