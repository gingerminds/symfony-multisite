<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

use Doctrine\Common\Collections\Collection;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * An entity whose language dependent fields live in a translation entity, one
 * row per language. TranslatableMetadataListener maps the `translations`
 * relation, TranslatableListener gives each entity the current languages.
 */
interface TranslatableInterface
{
    /**
     * @return class-string<TranslationInterface>
     */
    public static function getTranslationEntityClass(): string;

    /**
     * @return Collection<int, TranslationInterface>
     */
    public function getTranslations(): Collection;

    public function addTranslation(TranslationInterface $translation): void;

    public function removeTranslation(TranslationInterface $translation): void;

    /**
     * The translation in the current language, else in the fallback one (null when
     * neither exists); without language context, the first translation.
     */
    public function getCurrentTranslation(): ?TranslationInterface;

    /**
     * The translation in `$language` (null: the current one), falling back to the
     * fallback language one when it does not exist and `$fallback` is true.
     */
    public function getTranslation(LanguageInterface|int|null $language = null, bool $fallback = true): ?TranslationInterface;

    /**
     * Ids of the languages to try, best first (set by TranslatableListener).
     *
     * @param \Closure(): list<int> $languageIds
     */
    public function setTranslationLanguages(\Closure $languageIds): void;
}
