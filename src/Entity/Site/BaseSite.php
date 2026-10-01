<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Site;

use ApiPlatform\Metadata\ApiProperty;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\CacheableResourceInterface;
use Gingerminds\CoreBundle\Model\EagerLoadableInterface;
use Gingerminds\CoreBundle\Model\SearchableInterface;
use Gingerminds\CoreBundle\Model\SortableInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
#[UniqueEntity(fields: ['code'])]
abstract class BaseSite implements SiteInterface, TimestampableInterface, SortableInterface, SearchableInterface, EagerLoadableInterface, CacheableResourceInterface, \Stringable
{
    use SiteFrontUrlsTrait;
    use SiteGoogleDriveTrait;
    use SiteLanguagesTrait;
    use TimestampableTrait;

    public const string GROUP_LIST = 'site:list';
    public const string GROUP_READ = 'site:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: true)]
    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    protected ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    protected ?string $code = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Url(requireTld: false)]
    #[Assert\Length(max: 255)]
    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    protected ?string $url = null;

    public function __construct()
    {
        $this->siteLanguages = new ArrayCollection();
        $this->frontUrls = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public static function getSearchableFields(): array
    {
        return ['code', 'url'];
    }

    public static function getEagerLoads(): array
    {
        return ['siteLanguages', 'siteLanguages.language', 'frontUrls'];
    }

    public static function getCacheKey(): string
    {
        return 'site';
    }

    public static function getCacheTtl(): int
    {
        return 86400;
    }

    public function __toString(): string
    {
        return (string) $this->code;
    }
}
