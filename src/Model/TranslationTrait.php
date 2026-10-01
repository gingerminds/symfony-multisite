<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Exception\MappingException;

/**
 * Implements TranslationInterface. The owner class defaults to the translation
 * class without its `Translation` suffix (`ProductTranslation` → `Product`):
 * override getTranslatableEntityClass() otherwise.
 */
trait TranslationTrait
{
    /**
     * Mapped by TranslatableMetadataListener (target: getTranslatableEntityClass()).
     */
    protected ?TranslatableInterface $translatable = null;

    #[ORM\ManyToOne(targetEntity: LanguageInterface::class)]
    #[ORM\JoinColumn(name: 'language_id', nullable: false, onDelete: 'CASCADE')]
    protected ?LanguageInterface $language = null;

    public static function getTranslatableEntityClass(): string
    {
        $class = (string) preg_replace('/Translation$/', '', static::class);

        if (!is_a($class, TranslatableInterface::class, true)) {
            throw MappingException::invalidRelatedClass($class, TranslatableInterface::class, static::class, 'getTranslatableEntityClass');
        }

        return $class;
    }

    public function getTranslatable(): ?TranslatableInterface
    {
        return $this->translatable;
    }

    public function setTranslatable(?TranslatableInterface $translatable): void
    {
        $this->translatable = $translatable;
    }

    public function getLanguage(): ?LanguageInterface
    {
        return $this->language;
    }

    public function setLanguage(LanguageInterface $language): void
    {
        $this->language = $language;
    }

    public function isFor(LanguageInterface|int $language): bool
    {
        $id = $language instanceof LanguageInterface ? $language->getId() : $language;

        return $this->language === $language || (null !== $id && $this->language?->getId() === $id);
    }
}
