<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace ?>;

<?= $use_statements ?>

/**
 * One language of the <?= $resource->snake ?> translations (TranslationsType entry).
 *
 * @extends AbstractType<<?= $entity_class ?>>
 */
final class <?= $class_name ?> extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // TODO: add the translated fields.
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => <?= $entity_class ?>::class,
            'translation_domain' => 'admin',
        ]);
    }
}
