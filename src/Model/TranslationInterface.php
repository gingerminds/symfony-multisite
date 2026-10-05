<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * The translation of a TranslatableInterface entity in one language (one row per
 * language). TranslatableMetadataListener maps its owner relation (`<owner>_id`,
 * e.g. `product_id`) and the (owner, language) unique constraint.
 */
interface TranslationInterface
{
    /**
     * @return class-string<TranslatableInterface>
     */
    public static function getTranslatableEntityClass(): string;

    public function getTranslatable(): ?TranslatableInterface;

    public function setTranslatable(?TranslatableInterface $translatable): void;

    public function getLanguage(): ?LanguageInterface;

    public function setLanguage(LanguageInterface $language): void;

    public function isFor(LanguageInterface|int $language): bool;
}
