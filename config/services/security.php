<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\MultisiteBundle\Security\CredentialsEncryptor;
use Gingerminds\MultisiteBundle\Security\Voter\LanguageVoter;
use Gingerminds\MultisiteBundle\Security\Voter\SiteVoter;

/*
 * Voters and credentials encryption.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_multisite.security.voter.site', SiteVoter::class)
        ->tag('security.voter');
    $services->set('gingerminds_multisite.security.voter.language', LanguageVoter::class)
        ->tag('security.voter');

    $services->set('gingerminds_multisite.security.credentials_encryptor', CredentialsEncryptor::class)
        ->args([param('gingerminds_multisite.translation.encryption_key')]);
    $services->alias(CredentialsEncryptor::class, 'gingerminds_multisite.security.credentials_encryptor');
};
