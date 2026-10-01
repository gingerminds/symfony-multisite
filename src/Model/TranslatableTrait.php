<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * Implements TranslatableInterface. The translation class defaults to the
 * entity class followed by `Translation` (`Product` → `ProductTranslation`):
 * override getTranslationEntityClass() otherwise.
 *
 * Eager load the translations from getEagerLoads() (EagerLoadableInterface):
 * `[...self::getTranslationEagerLoads()]`, no query per row on lists.
 */
trait TranslatableTrait
{
    /**
     * Mapped by TranslatableMetadataListener (target: getTranslationEntityClass()).
     *
     * @var Collection<int, TranslationInterface>
     */
    protected Collection $translations;

    /**
     * @var (\Closure(): list<int>)|null
     */
    private ?\Closure $translationLanguages = null;

    public static function getTranslationEntityClass(): string
    {
        $class = static::class . 'Translation';

        if (!is_a($class, TranslationInterface::class, true)) {
            throw new \LogicException(\sprintf('"%s" must implement "%s", or override "%s::getTranslationEntityClass()".', $class, TranslationInterface::class, static::class));
        }

        return $class;
    }

    /**
     * @return list<string>
     */
    public static function getTranslationEagerLoads(): array
    {
        return ['translations', 'translations.language'];
    }

    public function getTranslations(): Collection
    {
        return $this->translations ??= new ArrayCollection();
    }

    public function addTranslation(TranslationInterface $translation): void
    {
        $translation->setTranslatable($this);

        if (!$this->getTranslations()->contains($translation)) {
            $this->getTranslations()->add($translation);
        }
    }

    public function removeTranslation(TranslationInterface $translation): void
    {
        $this->getTranslations()->removeElement($translation);
    }

    public function getCurrentTranslation(): ?TranslationInterface
    {
        $ids = null !== $this->translationLanguages ? ($this->translationLanguages)() : [];

        if ([] === $ids) {
            $first = $this->getTranslations()->first();

            return false === $first ? null : $first;
        }

        foreach ($ids as $id) {
            $translation = $this->findTranslation($id);

            if (null !== $translation) {
                return $translation;
            }
        }

        return null;
    }

    public function getTranslation(LanguageInterface|int|null $language = null, bool $fallback = true): ?TranslationInterface
    {
        if (null === $language) {
            return $this->getCurrentTranslation();
        }

        $translation = $this->findTranslation($language);

        if (null !== $translation || !$fallback || null === $this->translationLanguages) {
            return $translation;
        }

        $ids = ($this->translationLanguages)();
        $fallbackId = $ids[\count($ids) - 1] ?? null;

        return null !== $fallbackId ? $this->findTranslation($fallbackId) : null;
    }

    public function setTranslationLanguages(\Closure $languageIds): void
    {
        $this->translationLanguages = $languageIds;
    }

    private function findTranslation(LanguageInterface|int $language): ?TranslationInterface
    {
        foreach ($this->getTranslations() as $translation) {
            if ($translation->isFor($language)) {
                return $translation;
            }
        }

        return null;
    }
}
