<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

use Doctrine\Common\Collections\Collection;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * An entity attached to some languages (e.g. a media relevant to some of them):
 * the `gingerminds_language` Doctrine filter restricts its queries to the ones
 * attached to the current language. Its `languages` relation must be a
 * many-to-many owning side (LanguageContextedTrait maps one).
 */
interface LanguageContextedInterface extends LanguageScopedInterface
{
    public const string LANGUAGES_FIELD = 'languages';

    /**
     * @return Collection<int, LanguageInterface>
     */
    public function getLanguages(): Collection;

    public function addLanguage(LanguageInterface $language): void;

    public function removeLanguage(LanguageInterface $language): void;
}
