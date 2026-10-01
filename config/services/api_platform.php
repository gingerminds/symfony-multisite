<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CoreBundle\ApiPlatform\State\ResourceProvider;
use Gingerminds\MultisiteBundle\ApiPlatform\Metadata\ContextHeaderParameterProvider;
use Gingerminds\MultisiteBundle\Context\SiteContextResolver;
use Gingerminds\MultisiteBundle\Model\LanguageContextedInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;

/*
 * API Platform state provider and documented context headers.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_multisite.api.provider.site', ResourceProvider::class)
        ->args([service(SiteRepository::class), service('request_stack')])
        ->tag('api_platform.state_provider');

    $siteHeader = 'Restricts the response to the given site (id or code). Falls back to the request host, then to the first site, when omitted.';
    $languageHeader = 'Selects the translations and language scoped rows to return. Falls back to the site default language when omitted or unmatched.';

    foreach (
        [
            'site_contexted' => [SiteContextedInterface::class, SiteContextResolver::HEADER, $siteHeader],
            'language_contexted' => [LanguageContextedInterface::class, 'Accept-Language', $languageHeader],
            'translatable' => [TranslatableInterface::class, 'Accept-Language', $languageHeader],
        ] as $name => [$marker, $header, $description]
    ) {
        $services->set('gingerminds_multisite.api.header_parameter.' . $name, ContextHeaderParameterProvider::class)
            ->args([$marker, $header, $description])
            ->tag('gingerminds_core.api_header_parameter');
    }
};
