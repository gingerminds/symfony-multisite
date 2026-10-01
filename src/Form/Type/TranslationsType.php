<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Gingerminds\MultisiteBundle\Repository\Language\LanguageRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * The translations of a TranslatableInterface entity (its `translations`
 * property), one `entry_type` form per language, rendered as tabs: the site
 * languages (every language without current site), payload keyed by language id
 * (`translations[<language id>][<field>]`, as the Laravel form component).
 *
 * The default language translation is required and always validated; an optional
 * one left empty is neither created nor validated, and removed when it existed.
 *
 * @extends AbstractType<Collection<int, TranslationInterface>>
 */
class TranslationsType extends AbstractType implements DataMapperInterface
{
    public function __construct(
        protected readonly SiteContext $siteContext,
        protected readonly LanguageRepository $languages,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var list<LanguageInterface> $languages */
        $languages = $options['languages'];
        /** @var LanguageInterface|null $default */
        $default = $options['default_language'];
        /** @var class-string<TranslationInterface>|null $translationClass */
        $translationClass = $builder->getFormFactory()->createBuilder($options['entry_type'], null, $options['entry_options'])->getDataClass();

        foreach ($languages as $language) {
            $isDefault = $language === $default;

            $builder->add((string) $language->getId(), $options['entry_type'], [
                'label' => false,
                ...$options['entry_options'],
                'required' => $isDefault,
                'constraints' => [new Valid()],
                // An optional language left empty is not validated (nor saved, see mapFormsToData()).
                'validation_groups' => static fn (FormInterface $form): array => $isDefault || !self::isBlank($form) ? self::rootGroups($form) : [],
                'empty_data' => static function () use ($translationClass, $language): ?TranslationInterface {
                    if (null === $translationClass) {
                        return null;
                    }

                    $translation = new $translationClass();
                    $translation->setLanguage($language);

                    return $translation;
                },
            ]);
        }

        $builder->setAttribute('default_language', $default);
        $builder->setDataMapper($this);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var LanguageInterface|null $default */
        $default = $options['default_language'];
        $languages = [];

        foreach ($options['languages'] as $language) {
            /** @var LanguageInterface $language */
            $languages[(string) $language->getId()] = [
                'iso' => (string) $language->getIso(),
                'label' => (string) $language->getLabel(),
                'default' => $language === $default,
            ];
        }

        $view->vars['translation_languages'] = $languages;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('entry_type')
            ->setAllowedTypes('entry_type', 'string')
            ->setDefaults([
                'entry_options' => [],
                'languages' => fn (Options $options): array => $this->defaultLanguages(),
                'default_language' => function (Options $options): ?LanguageInterface {
                    /** @var list<LanguageInterface> $languages */
                    $languages = $options['languages'];
                    $default = $this->siteContext->site()?->getDefaultLanguage();

                    return \in_array($default, $languages, true) ? $default : ($languages[0] ?? null);
                },
                'label' => false,
                // Each language entry sets its own (`required` is inherited: the default language one only).
                'required' => true,
                'error_bubbling' => false,
            ])
            ->setAllowedTypes('entry_options', 'array')
            ->setAllowedTypes('languages', 'array')
            ->setAllowedTypes('default_language', ['null', LanguageInterface::class]);
    }

    public function getBlockPrefix(): string
    {
        return 'gingerminds_translations';
    }

    /**
     * @param \Traversable<FormInterface<mixed>> $forms
     */
    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        foreach ($forms as $form) {
            $form->setData($this->find($viewData, (int) $form->getName()));
        }
    }

    /**
     * @param \Traversable<FormInterface<mixed>> $forms
     */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        foreach ($forms as $form) {
            $translation = $form->getData();
            $owner = $form->getParent()?->getParent()?->getData();

            if (!$translation instanceof TranslationInterface || !$owner instanceof TranslatableInterface) {
                continue;
            }

            $isDefault = $form->getParent()?->getConfig()->getAttribute('default_language') === $translation->getLanguage();
            $exists = $owner->getTranslations()->contains($translation);

            if (!$isDefault && self::isBlank($form)) {
                if ($exists) {
                    $owner->removeTranslation($translation);
                }

                continue;
            }

            if (!$exists) {
                $owner->addTranslation($translation);
            }
        }
    }

    /**
     * @return list<LanguageInterface>
     */
    protected function defaultLanguages(): array
    {
        $site = $this->siteContext->site();

        return $site instanceof SiteInterface ? $site->getLanguages() : $this->languages->findAllOrdered();
    }

    private function find(mixed $translations, int $languageId): ?TranslationInterface
    {
        if (!is_iterable($translations)) {
            return null;
        }

        foreach ($translations as $translation) {
            if ($translation instanceof TranslationInterface && $translation->isFor($languageId)) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * Nothing submitted in the form fields (FormInterface::isEmpty() is false for a
     * compound form whose data is an object, here the `empty_data` translation).
     *
     * @param FormInterface<mixed> $form
     */
    private static function isBlank(FormInterface $form): bool
    {
        if ($form->getConfig()->getCompound()) {
            return array_all($form->all(), static fn (FormInterface $child): bool => self::isBlank($child));
        }

        $value = $form->getViewData();

        return null === $value || '' === $value || [] === $value || false === $value;
    }

    /**
     * The validation groups of the root form (Default when none).
     *
     * @param FormInterface<mixed> $form
     *
     * @return array<string>
     */
    private static function rootGroups(FormInterface $form): array
    {
        $root = $form->getRoot();
        $groups = $root->getConfig()->getOption('validation_groups');

        if (\is_callable($groups)) {
            $groups = $groups($root);
        }

        return \is_array($groups) && [] !== $groups ? array_values(array_map(strval(...), $groups)) : ['Default'];
    }
}
