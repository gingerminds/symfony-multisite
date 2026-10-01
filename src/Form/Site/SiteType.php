<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Form\Site;

use Gingerminds\CoreBundle\Resource\ResourceRegistry;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Form\DataTransformer\LinesToArrayTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<SiteInterface>
 */
class SiteType extends AbstractType
{
    public const string CREDENTIALS_FIELD = 'google_service_account_credentials';

    public function __construct(
        protected readonly ResourceRegistry $resources,
        protected readonly TranslatorInterface $translator,
        protected readonly bool $translationEnabled,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $languageClass = $this->resources->getEntityClass('language');

        $builder
            ->add('code', TextType::class, [
                'label' => 'site.field.code',
            ])
            ->add('url', UrlType::class, [
                'label' => 'site.field.url',
                'default_protocol' => null,
            ])
            ->add('front_urls', TextareaType::class, [
                'label' => 'site.field.front_urls',
                'help' => 'site.help.front_urls',
                'property_path' => 'frontUrlValues',
                'required' => false,
                'size' => 'xl',
                'attr' => ['rows' => 4],
                'constraints' => [new Assert\All([new Assert\Url(requireTld: false), new Assert\Length(max: 255)])],
            ])
            ->add('languages', EntityType::class, [
                'label' => 'site.field.languages',
                'class' => $languageClass,
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
                'by_reference' => false,
                'size' => 'xl',
                'choice_translation_domain' => false,
            ])
            ->add('default_language', EntityType::class, [
                'label' => 'site.field.default_language',
                'class' => $languageClass,
                'property_path' => 'defaultLanguage',
                'required' => false,
                'placeholder' => 'site.placeholder.default_language',
                'size' => 'xl',
                'choice_translation_domain' => false,
            ]);

        $builder->get('front_urls')->addModelTransformer(new LinesToArrayTransformer());

        if ($this->translationEnabled) {
            $builder
                ->add('google_drive_file_id', TextType::class, [
                    'label' => 'site.field.google_drive_file_id',
                    'property_path' => 'googleDriveFileId',
                    'required' => false,
                    'size' => 'xl',
                ])
                ->add(self::CREDENTIALS_FIELD, TextareaType::class, [
                    'label' => 'site.field.google_service_account_credentials',
                    'help' => 'site.help.google_service_account_credentials',
                    'mapped' => false,
                    'required' => false,
                    'size' => 'xl',
                    'attr' => ['rows' => 6, 'placeholder' => '{"type": "service_account", "client_email": "...", "private_key": "..."}'],
                    'constraints' => [new Assert\Json()],
                ]);
        }

        // The default language must be one of the selected ones.
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $default = $form->get('default_language')->getData();
            $languages = $form->get('languages')->getData();

            if (!$default instanceof LanguageInterface || !is_iterable($languages)) {
                return;
            }

            foreach ($languages as $language) {
                if ($language === $default) {
                    return;
                }
            }

            $form->get('default_language')->addError(new FormError(
                $this->translator->trans('site.error.default_language_not_selected', [], 'GingermindsMultisite'),
            ));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->resources->getEntityClass('site'),
            'translation_domain' => 'GingermindsMultisite',
        ]);
    }
}
