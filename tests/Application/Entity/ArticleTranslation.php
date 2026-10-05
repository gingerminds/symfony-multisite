<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Gingerminds\MultisiteBundle\Model\TranslationTrait;
use Gingerminds\MultisiteBundle\Validator\UniqueTranslationSlug;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'article_translations')]
#[UniqueTranslationSlug]
class ArticleTranslation implements TranslationInterface
{
    use TranslationTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $title = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $slug = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }
}
