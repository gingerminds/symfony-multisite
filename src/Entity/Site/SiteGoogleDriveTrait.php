<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Site;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Google Drive front translations file of a BaseSite and its encrypted service account credentials.
 */
trait SiteGoogleDriveTrait
{
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    protected ?string $googleDriveFileId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $googleServiceAccountCredentials = null;

    public function getGoogleDriveFileId(): ?string
    {
        return $this->googleDriveFileId;
    }

    public function setGoogleDriveFileId(?string $fileId): void
    {
        $this->googleDriveFileId = null === $fileId || '' === trim($fileId) ? null : trim($fileId);
    }

    public function getEncryptedGoogleCredentials(): ?string
    {
        return $this->googleServiceAccountCredentials;
    }

    public function setEncryptedGoogleCredentials(?string $credentials): void
    {
        $this->googleServiceAccountCredentials = $credentials;
    }

    public function hasGoogleCredentials(): bool
    {
        return null !== $this->googleServiceAccountCredentials && '' !== $this->googleServiceAccountCredentials;
    }
}
