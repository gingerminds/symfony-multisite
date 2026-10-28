<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\ApiPlatform\Metadata;

use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Gingerminds\MultisiteBundle\Context\SiteContextResolver;
use Gingerminds\MultisiteBundle\Model\LanguageContextedInterface;
use Gingerminds\MultisiteBundle\Model\LanguageScoped;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;
use Gingerminds\MultisiteBundle\Model\SiteScoped;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;

/**
 * Documents the context headers on the operations of the resources depending on them:
 * `X-Site-Id` (site contexted, `#[SiteScoped]`), `Accept-Language` (translatable, language
 * contexted, `#[LanguageScoped]`).
 */
final readonly class ContextHeaderParametersResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private const string SITE_DESCRIPTION = 'Restricts the response to the given site (id or code). Falls back to the request host, then to the first site, when omitted.';
    private const string LANGUAGE_DESCRIPTION = 'Selects the language of the response (translations, language scoped rows): '
        . 'the first language enabled on the site, else its default language.';

    public function __construct(
        private ResourceMetadataCollectionFactoryInterface $decorated,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $collection = $this->decorated->create($resourceClass);
        $headerParameters = $this->headerParameters($resourceClass);

        if ([] === $headerParameters) {
            return $collection;
        }

        foreach ($collection as $i => $resource) {
            $operations = $resource->getOperations();

            if (!$operations instanceof Operations) {
                continue;
            }

            foreach ($operations as $name => $operation) {
                $parameters = $operation->getParameters() ?? new Parameters();

                foreach ($headerParameters as $headerParameter) {
                    $parameters->add((string) $headerParameter->getKey(), $headerParameter);
                }

                $operations->add($name, $operation->withParameters($parameters));
            }

            $collection[$i] = $resource->withOperations($operations->sort());
        }

        return $collection;
    }

    /**
     * @return list<HeaderParameter>
     */
    private function headerParameters(string $resourceClass): array
    {
        if (!class_exists($resourceClass)) {
            return [];
        }

        $parameters = [];

        if (is_a($resourceClass, SiteContextedInterface::class, true) || $this->hasAttribute($resourceClass, SiteScoped::class)) {
            $parameters[] = new HeaderParameter(key: SiteContextResolver::HEADER, schema: ['type' => 'string'], description: self::SITE_DESCRIPTION);
        }

        if (
            is_a($resourceClass, TranslatableInterface::class, true)
            || is_a($resourceClass, LanguageContextedInterface::class, true)
            || $this->hasAttribute($resourceClass, LanguageScoped::class)
        ) {
            $parameters[] = new HeaderParameter(key: 'Accept-Language', schema: ['type' => 'string'], description: self::LANGUAGE_DESCRIPTION);
        }

        return $parameters;
    }

    /**
     * The attribute on the class or one of its parents (an entity overriding a bundle one).
     *
     * @param class-string $class
     * @param class-string $attribute
     */
    private function hasAttribute(string $class, string $attribute): bool
    {
        for ($reflection = new \ReflectionClass($class); false !== $reflection; $reflection = $reflection->getParentClass()) {
            if ([] !== $reflection->getAttributes($attribute)) {
                return true;
            }
        }

        return false;
    }
}
