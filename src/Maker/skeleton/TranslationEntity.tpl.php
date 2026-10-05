<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace ?>;

<?= $use_statements ?>

/**
 * Translation of <?= $owner_class ?> in one language: its language dependent fields.
 */
#[ORM\Entity]
#[ORM\Table(name: '<?= $table ?>')]
class <?= $class_name ?> implements TranslationInterface
{
    use TranslationTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
