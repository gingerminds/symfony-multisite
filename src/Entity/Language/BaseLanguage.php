<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Language;

use ApiPlatform\Metadata\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\CacheableResourceInterface;
use Gingerminds\CoreBundle\Model\CacheCascadeInterface;
use Gingerminds\CoreBundle\Model\SearchableInterface;
use Gingerminds\CoreBundle\Model\SortableInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Site\BaseSite;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
#[UniqueEntity(fields: ['iso'])]
abstract class BaseLanguage implements LanguageInterface, TimestampableInterface, SortableInterface, SearchableInterface, CacheableResourceInterface, CacheCascadeInterface
{
    use TimestampableTrait;

    public const string GROUP_LIST = 'language:list';
    public const string GROUP_READ = 'language:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: true)]
    #[Groups([self::GROUP_LIST, self::GROUP_READ, BaseSite::GROUP_LIST, BaseSite::GROUP_READ])]
    protected ?int $id = null;

    #[ORM\Column(length: 7, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 7)]
    #[Groups([self::GROUP_LIST, self::GROUP_READ, BaseSite::GROUP_LIST, BaseSite::GROUP_READ])]
    protected ?string $iso = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups([self::GROUP_LIST, self::GROUP_READ, BaseSite::GROUP_LIST, BaseSite::GROUP_READ])]
    protected ?string $label = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIso(): ?string
    {
        return $this->iso;
    }

    public function setIso(string $iso): void
    {
        $this->iso = mb_strtolower(trim($iso));
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public static function getSearchableFields(): array
    {
        return ['iso', 'label'];
    }

    public static function getCacheKey(): string
    {
        return 'language';
    }

    public static function getCacheTtl(): int
    {
        return 86400;
    }

    public static function getCascadeCacheKeys(): array
    {
        return ['site'];
    }

    public function __toString(): string
    {
        return \sprintf('%s (%s)', $this->label, mb_strtoupper((string) $this->iso));
    }
}
