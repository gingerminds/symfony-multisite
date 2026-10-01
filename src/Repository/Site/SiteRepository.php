<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Repository\Site;

use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CoreBundle\Repository\AbstractRepository;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Form\Site\SiteType;
use Gingerminds\MultisiteBundle\Security\CredentialsEncryptor;
use Symfony\Component\Form\FormInterface;

/**
 * @extends AbstractRepository<SiteInterface>
 */
class SiteRepository extends AbstractRepository
{
    /**
     * @param class-string<SiteInterface> $entityClass
     */
    public function __construct(
        ManagerRegistry $registry,
        string $entityClass,
        private readonly CredentialsEncryptor $encryptor,
    ) {
        parent::__construct($registry, $entityClass);
    }

    /**
     * @return list<SiteInterface>
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['code' => 'ASC']);
    }

    /**
     * @param SiteInterface $entity
     */
    protected function beforeSave(object $entity, ?FormInterface $form): void
    {
        if (!$form instanceof FormInterface || !$form->has(SiteType::CREDENTIALS_FIELD)) {
            return;
        }

        $credentials = $form->get(SiteType::CREDENTIALS_FIELD)->getData();

        if (!\is_string($credentials) || '' === trim($credentials)) {
            return;
        }

        $decoded = json_decode($credentials, true, flags: \JSON_THROW_ON_ERROR);

        if (\is_array($decoded)) {
            $entity->setEncryptedGoogleCredentials($this->encryptor->encrypt($decoded));
        }
    }
}
