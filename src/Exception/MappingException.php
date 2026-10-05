<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Exception;

/**
 * A project entity does not follow the conventions of the multisite traits.
 */
final class MappingException extends \LogicException
{
    public static function languagesNotOwningManyToMany(string $entity, string $field): self
    {
        return new self(\sprintf('"%s::$%s" must be a many-to-many owning side.', $entity, $field));
    }

    /**
     * @param string $class     expected related class
     * @param string $interface interface it must implement
     * @param string $entity    entity whose method resolves it
     * @param string $method    method to override otherwise
     */
    public static function invalidRelatedClass(string $class, string $interface, string $entity, string $method): self
    {
        return new self(\sprintf('"%s" must implement "%s", or override "%s::%s()".', $class, $interface, $entity, $method));
    }
}
