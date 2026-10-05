<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Model;

/**
 * For a TranslatableInterface entity whose translation has `getTitle()` and
 * `getSlug()`: the current ones, and the slug of every titled translation by
 * language ISO code (language switcher of the front).
 */
trait HasTranslatedTitleAndSlugTrait
{
    public function getTitle(): ?string
    {
        $translation = $this->getCurrentTranslation();

        return null !== $translation && method_exists($translation, 'getTitle') ? $translation->getTitle() : null;
    }

    public function getSlug(): ?string
    {
        $translation = $this->getCurrentTranslation();

        return null !== $translation && method_exists($translation, 'getSlug') ? $translation->getSlug() : null;
    }

    /**
     * @return array<string, string|null> ISO code => slug
     */
    public function getSwitchLang(): array
    {
        $paths = [];

        foreach ($this->getTranslations() as $translation) {
            $iso = $translation->getLanguage()?->getIso();

            if (null === $iso || !method_exists($translation, 'getTitle') || null === $translation->getTitle() || '' === $translation->getTitle()) {
                continue;
            }

            $paths[$iso] = method_exists($translation, 'getSlug') ? $translation->getSlug() : null;
        }

        return $paths;
    }
}
