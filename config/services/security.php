<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\MultisiteBundle\Security\CredentialsEncryptor;
use Gingerminds\MultisiteBundle\Security\SiteAccess;

/*
 * Site access of the users and credentials encryption (the site and language permissions: core ResourceVoter).
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_multisite.security.site_access', SiteAccess::class)
        ->args([service('security.helper')]);
    $services->alias(SiteAccess::class, 'gingerminds_multisite.security.site_access');

    $services->set('gingerminds_multisite.security.credentials_encryptor', CredentialsEncryptor::class)
        ->args([param('gingerminds_multisite.translation.encryption_key')]);
    $services->alias(CredentialsEncryptor::class, 'gingerminds_multisite.security.credentials_encryptor');
};
