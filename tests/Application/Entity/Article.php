<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\EagerLoadableInterface;
use Gingerminds\MultisiteBundle\Model\HasTranslatedTitleAndSlugTrait;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedTrait;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableTrait;

/**
 * Site contexted and translatable test entity (translations: ArticleTranslation).
 */
#[ORM\Entity]
#[ORM\Table(name: 'articles')]
class Article implements SiteContextedInterface, TranslatableInterface, EagerLoadableInterface
{
    use HasTranslatedTitleAndSlugTrait;
    use SiteContextedTrait;
    use TranslatableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 50)]
        private string $code,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public static function getEagerLoads(): array
    {
        return [...self::getTranslationEagerLoads()];
    }
}
