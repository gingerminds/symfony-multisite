<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\MultisiteBundle\Form\Language\LanguageType;
use Gingerminds\MultisiteBundle\Form\Site\SiteType;
use Gingerminds\MultisiteBundle\Form\Type\TranslationsType;
use Gingerminds\MultisiteBundle\Repository\Language\LanguageRepository;
use Gingerminds\MultisiteBundle\Validator\UniqueTranslationSlugValidator;

/*
 * Form types and validators.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_multisite.form.type.site', SiteType::class)
        ->args([
            service('gingerminds_core.resource_registry'),
            service('translator'),
            param('gingerminds_multisite.translation.enabled'),
        ])
        ->tag('form.type');
    $services->set('gingerminds_multisite.form.type.language', LanguageType::class)
        ->args([service('gingerminds_core.resource_registry')])
        ->tag('form.type');

    $services->set('gingerminds_multisite.form.type.translations', TranslationsType::class)
        ->args([service('gingerminds_multisite.context.site'), service(LanguageRepository::class)])
        ->tag('form.type');

    $services->set('gingerminds_multisite.validator.unique_translation_slug', UniqueTranslationSlugValidator::class)
        ->args([service('doctrine'), service('gingerminds_multisite.context.site')])
        ->tag('validator.constraint_validator');
};
