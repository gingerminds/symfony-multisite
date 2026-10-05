<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Gingerminds\MultisiteBundle\Model\LanguageScopedInterface;
use Gingerminds\MultisiteBundle\Model\SiteScopedInterface;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * `GET /api/translations` (public): the front translations of the current site,
 * one item per locale; only the Accept-Language one when the header is sent.
 * `?site=` (id or code) targets another site.
 */
#[ApiResource(
    shortName: 'Translation',
    operations: [
        new GetCollection(
            uriTemplate: '/translations',
            paginationEnabled: false,
            normalizationContext: ['groups' => [self::GROUP_READ]],
            provider: 'gingerminds_multisite.api.provider.translation',
        ),
    ],
)]
final readonly class Translation implements SiteScopedInterface, LanguageScopedInterface
{
    public const string GROUP_READ = 'translation:read';

    /**
     * @param array<string, string> $values key => value
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        #[Groups([self::GROUP_READ])]
        public string $locale,
        #[Groups([self::GROUP_READ])]
        public array $values,
    ) {
    }
}
