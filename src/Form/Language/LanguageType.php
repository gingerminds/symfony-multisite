<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Form\Language;

use Gingerminds\CoreBundle\Resource\ResourceRegistry;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<LanguageInterface>
 */
class LanguageType extends AbstractType
{
    public function __construct(
        protected readonly ResourceRegistry $resources,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('iso', TextType::class, [
                'label' => 'language.field.iso',
                'help' => 'language.help.iso',
                'size' => 'sm',
                'attr' => ['maxlength' => 7],
            ])
            ->add('label', TextType::class, [
                'label' => 'language.field.label',
                'size' => 'lg',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->resources->getEntityClass('language'),
            'translation_domain' => 'GingermindsMultisite',
        ]);
    }
}
