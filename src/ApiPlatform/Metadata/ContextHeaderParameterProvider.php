<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\ApiPlatform\Metadata;

use ApiPlatform\Metadata\HeaderParameter;
use Gingerminds\CoreBundle\ApiPlatform\Metadata\HeaderParameterProviderInterface;

/**
 * Documents a context header (`X-Site-Id`, `Accept-Language`) on the API
 * operations of the resources implementing `$marker` (core HeaderParameterRegistry).
 */
final readonly class ContextHeaderParameterProvider implements HeaderParameterProviderInterface
{
    /**
     * @param class-string $marker
     */
    public function __construct(
        private string $marker,
        private string $header,
        private string $description,
    ) {
    }

    public function getMarker(): string
    {
        return $this->marker;
    }

    public function getHeaderParameter(): HeaderParameter
    {
        return new HeaderParameter(key: $this->header, schema: ['type' => 'string'], description: $this->description);
    }
}
