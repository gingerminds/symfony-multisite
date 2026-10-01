<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Site;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\CacheCascadeInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * Language enabled on a site, `is_default` on at most one per site.
 */
#[ORM\Entity]
#[ORM\Table(name: 'site_language')]
#[ORM\UniqueConstraint(name: 'site_language_unique', columns: ['site_id', 'language_id'])]
class SiteLanguage implements TimestampableInterface, CacheCascadeInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'is_default', options: ['default' => false])]
    private bool $default = false;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: SiteInterface::class, inversedBy: 'siteLanguages')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private SiteInterface $site,
        #[ORM\ManyToOne(targetEntity: LanguageInterface::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private LanguageInterface $language,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSite(): SiteInterface
    {
        return $this->site;
    }

    public function getLanguage(): LanguageInterface
    {
        return $this->language;
    }

    public function isFor(LanguageInterface $language): bool
    {
        return $this->language === $language || (null !== $language->getId() && $this->language->getId() === $language->getId());
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }

    public static function getCascadeCacheKeys(): array
    {
        return ['site'];
    }
}
