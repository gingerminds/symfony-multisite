<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Repository\Language;

use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CoreBundle\Repository\AbstractRepository;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * @extends AbstractRepository<LanguageInterface>
 */
class LanguageRepository extends AbstractRepository
{
    /**
     * @param class-string<LanguageInterface> $entityClass
     */
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * @return list<LanguageInterface>
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['label' => 'ASC']);
    }
}
