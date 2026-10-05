<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Maker;

use Gingerminds\CoreBundle\Maker\Extension\ResourceMakerContext;
use Gingerminds\CoreBundle\Maker\Extension\ResourceMakerExtensionInterface;
use Gingerminds\CoreBundle\Maker\Extension\SkeletonTemplate;
use Gingerminds\CoreBundle\Maker\ResourceGenerator;
use Gingerminds\CoreBundle\Maker\ResourceName;
use Gingerminds\MultisiteBundle\Form\Type\TranslationsType;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableTrait;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Gingerminds\MultisiteBundle\Model\TranslationTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * `--translated` option of the make:gm:* makers: a translatable resource with its
 * `<Name>Translation` entity (no field: add the translated ones), the
 * `<Name>TranslationType` form and a TranslationsType field; the template has General / Translations tabs,
 * the language tabs in the second.
 */
final class TranslatedResourceMakerExtension implements ResourceMakerExtensionInterface
{
    public const string OPTION = 'translated';

    /**
     * Makers taking the option => what they generate (entity, form, template).
     */
    private const array COMMANDS = [
        'make:gm:resource' => ['entity', 'form', 'template'],
        'make:gm:entity' => ['entity'],
        'make:gm:form' => ['form'],
        'make:gm:crud-controller' => ['template'],
    ];

    private const string SKELETON_DIRECTORY = __DIR__ . '/skeleton';

    public function configureCommand(string $commandName, Command $command): void
    {
        if (isset(self::COMMANDS[$commandName])) {
            $command->addOption(
                self::OPTION,
                null,
                InputOption::VALUE_NONE,
                'Translatable resource: <Name>Translation entity and form, translations as language tabs (gingerminds/symfony-multisite)',
            );
        }
    }

    public function isEnabled(InputInterface $input): bool
    {
        return $input->hasOption(self::OPTION) && true === $input->getOption(self::OPTION);
    }

    public function configureTemplate(SkeletonTemplate $template, ResourceName $resource): void
    {
        match ($template->name) {
            'Entity.tpl.php' => $this->configureEntity($template),
            'FormType.tpl.php' => $this->configureForm($template, $resource),
            'twig/_form.tpl.php' => $template->path = self::SKELETON_DIRECTORY . '/twig/_form.tpl.php',
            default => null,
        };
    }

    public function generate(ResourceMakerContext $context): array
    {
        $parts = self::COMMANDS[$context->commandName] ?? [];
        $resource = $context->resource;
        $translation = $this->translationName($context);
        $nextSteps = [];

        if (\in_array('entity', $parts, true)) {
            $context->resourceGenerator->generateClassFromSkeleton($context->generator, $context->io, $translation->entityClass(), new SkeletonTemplate(
                'TranslationEntity.tpl.php',
                self::SKELETON_DIRECTORY . '/TranslationEntity.tpl.php',
                ['table' => $resource->snake . '_translations', 'owner_class' => ResourceGenerator::shortName($resource->entityClass())],
                ['Doctrine\\ORM\\Mapping as ORM', TranslationInterface::class, TranslationTrait::class],
            ));
            $nextSteps[] = \sprintf(
                "Add the translated fields with <fg=yellow>bin/console make:entity '%s'</> (table <comment>%s_translations</comment>, "
                . 'joined on <comment>%s_id</comment> + <comment>language_id</comment>); <comment>#[UniqueTranslationSlug]</comment> for a slug.',
                $translation->relativeClassName(),
                $resource->snake,
                $resource->snake,
            );
        }

        if (\in_array('form', $parts, true)) {
            $context->resourceGenerator->generateClassFromSkeleton($context->generator, $context->io, $translation->formClass(), new SkeletonTemplate(
                'TranslationFormType.tpl.php',
                self::SKELETON_DIRECTORY . '/TranslationFormType.tpl.php',
                ['resource' => $resource, 'entity_class' => ResourceGenerator::shortName($translation->entityClass())],
                [AbstractType::class, FormBuilderInterface::class, OptionsResolver::class, $translation->entityClass()],
            ));
            $nextSteps[] = \sprintf('Add the translated fields to <comment>%s</comment> (one form per language, the default language one required).', $translation->formClass());
        }

        if (\in_array('template', $parts, true)) {
            $context->resourceGenerator->addTranslations($context->generator, $context->io, 'admin', [
                $resource->snake => [
                    'tab' => ['general' => 'General'],
                    'field' => ['translations' => 'Translations'],
                ],
            ]);
        }

        return $nextSteps;
    }

    private function configureEntity(SkeletonTemplate $template): void
    {
        $template->addUse(TranslatableInterface::class, TranslatableTrait::class);
        $template->append('interfaces', 'TranslatableInterface');
        $template->append('traits', 'TranslatableTrait');
        $template->append('eager_loads', '...self::getTranslationEagerLoads()');
    }

    private function configureForm(SkeletonTemplate $template, ResourceName $resource): void
    {
        $translationType = ResourceGenerator::shortName($resource->formClass());
        $translationType = substr($translationType, 0, -\strlen('Type')) . 'TranslationType';

        $template->addUse(TranslationsType::class);
        $template->append(
            'build_form',
            "        \$builder->add('translations', TranslationsType::class, [",
            '            \'entry_type\' => ' . $translationType . '::class,',
            '        ]);',
        );
    }

    private function translationName(ResourceMakerContext $context): ResourceName
    {
        return ResourceName::fromInput($context->resource->argument() . 'Translation', $context->generator->getRootNamespace());
    }
}
