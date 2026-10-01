<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Model\LanguageContextedInterface;
use Gingerminds\MultisiteBundle\Model\LanguageContextedTrait;

/**
 * Language contexted test entity (join table `media_language`).
 */
#[ORM\Entity]
#[ORM\Table(name: 'medias')]
class Media implements LanguageContextedInterface
{
    use LanguageContextedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 50)]
        private string $name,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
