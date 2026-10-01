<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Site;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\CacheCascadeInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;

/**
 * Domain of a front-end site linked to the back office (serialized as `front_urls`).
 */
#[ORM\Entity]
#[ORM\Table(name: 'site_front_urls')]
#[ORM\UniqueConstraint(name: 'site_front_urls_unique', columns: ['site_id', 'url'])]
class SiteFrontUrl implements TimestampableInterface, CacheCascadeInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: SiteInterface::class, inversedBy: 'frontUrls')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private SiteInterface $site,
        #[ORM\Column(length: 255)]
        private string $url,
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

    public function getUrl(): string
    {
        return $this->url;
    }

    public static function getCascadeCacheKeys(): array
    {
        return ['site'];
    }
}
