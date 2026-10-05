<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Language;

use Gingerminds\CoreBundle\Model\ResourceInterface;

/**
 * Displayed as "Label (ISO)" in the admin choices.
 */
interface LanguageInterface extends ResourceInterface, \Stringable
{
    public function getId(): ?int;

    /**
     * Lowercase ISO code (`fr`, `en`...), matched against the Accept-Language header.
     */
    public function getIso(): ?string;

    public function setIso(string $iso): void;

    public function getLabel(): ?string;

    public function setLabel(string $label): void;
}
