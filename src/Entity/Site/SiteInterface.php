<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Site;

use Doctrine\Common\Collections\Collection;
use Gingerminds\CoreBundle\Model\ResourceInterface;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

interface SiteInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getCode(): ?string;

    public function setCode(string $code): void;

    public function getUrl(): ?string;

    public function setUrl(string $url): void;

    /**
     * @return Collection<int, SiteLanguage>
     */
    public function getSiteLanguages(): Collection;

    /**
     * @return list<LanguageInterface>
     */
    public function getLanguages(): array;

    /**
     * Replaces the languages of the site, the default flag of the kept ones is preserved.
     *
     * @param iterable<LanguageInterface> $languages
     */
    public function setLanguages(iterable $languages): void;

    public function hasLanguage(LanguageInterface $language): bool;

    public function getDefaultLanguage(): ?LanguageInterface;

    /**
     * Flags one of the site languages as default (null: none); a language of another site is ignored.
     */
    public function setDefaultLanguage(?LanguageInterface $language): void;

    /**
     * @return Collection<int, SiteFrontUrl>
     */
    public function getFrontUrls(): Collection;

    /**
     * @return list<string>
     */
    public function getFrontUrlValues(): array;

    /**
     * @param iterable<string> $urls
     */
    public function setFrontUrlValues(iterable $urls): void;

    public function getGoogleDriveFileId(): ?string;

    public function setGoogleDriveFileId(?string $fileId): void;

    /**
     * Google service account credentials, encrypted (CredentialsEncryptor).
     */
    public function getEncryptedGoogleCredentials(): ?string;

    public function setEncryptedGoogleCredentials(?string $credentials): void;

    public function hasGoogleCredentials(): bool;
}
