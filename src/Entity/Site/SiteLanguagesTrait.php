<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Site;

use Doctrine\Common\Collections\Collection;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Languages of a BaseSite (`site_language`), one of them flagged as default.
 */
trait SiteLanguagesTrait
{
    /**
     * @var Collection<int, SiteLanguage>
     */
    protected Collection $siteLanguages;

    public function getSiteLanguages(): Collection
    {
        return $this->siteLanguages;
    }

    #[Groups([BaseSite::GROUP_LIST, BaseSite::GROUP_READ])]
    #[SerializedName('languages')]
    public function getLanguages(): array
    {
        $languages = [];

        foreach ($this->siteLanguages as $siteLanguage) {
            $languages[] = $siteLanguage->getLanguage();
        }

        return $languages;
    }

    public function setLanguages(iterable $languages): void
    {
        $kept = [];

        foreach ($languages as $language) {
            $kept[] = $language;

            if (!$this->hasLanguage($language)) {
                $this->siteLanguages->add(new SiteLanguage($this, $language));
            }
        }

        foreach ($this->siteLanguages as $siteLanguage) {
            if (!array_any($kept, static fn (LanguageInterface $language): bool => $siteLanguage->isFor($language))) {
                $this->siteLanguages->removeElement($siteLanguage);
            }
        }
    }

    public function hasLanguage(LanguageInterface $language): bool
    {
        return $this->siteLanguages->exists(static fn (int $key, SiteLanguage $siteLanguage): bool => $siteLanguage->isFor($language));
    }

    #[Groups([BaseSite::GROUP_LIST, BaseSite::GROUP_READ])]
    #[SerializedName('default_language')]
    public function getDefaultLanguage(): ?LanguageInterface
    {
        foreach ($this->siteLanguages as $siteLanguage) {
            if ($siteLanguage->isDefault()) {
                return $siteLanguage->getLanguage();
            }
        }

        return null;
    }

    public function setDefaultLanguage(?LanguageInterface $language): void
    {
        foreach ($this->siteLanguages as $siteLanguage) {
            $siteLanguage->setDefault($language instanceof LanguageInterface && $siteLanguage->isFor($language));
        }
    }
}
