<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * Implements LanguageContextedInterface. The join table defaults to
 * `<entity>_language` / `language_id` (e.g. `media_language`, see
 * LanguageContextedMetadataListener): rename it with an
 * #[ORM\AssociationOverride(name: 'languages', joinTable: ...)].
 */
trait LanguageContextedTrait
{
    /**
     * @var Collection<int, LanguageInterface>
     */
    #[ORM\ManyToMany(targetEntity: LanguageInterface::class)]
    protected Collection $languages;

    public function getLanguages(): Collection
    {
        return $this->languages ??= new ArrayCollection();
    }

    public function addLanguage(LanguageInterface $language): void
    {
        if (!$this->getLanguages()->contains($language)) {
            $this->getLanguages()->add($language);
        }
    }

    public function removeLanguage(LanguageInterface $language): void
    {
        $this->getLanguages()->removeElement($language);
    }
}
