<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Application\Form;

use Gingerminds\MultisiteBundle\Form\Type\TranslationsType;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Article>
 */
final class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('translations', TranslationsType::class, [
            'entry_type' => ArticleTranslationType::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Article::class]);
    }
}
