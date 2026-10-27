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

    public function findFirst(): ?SiteInterface
    {
        return $this->findOneBy([], ['id' => 'ASC']);
    }

    /**
     * The first site whose URL host is exactly the host (e.g. `brand.example.com` for
     * `https://brand.example.com/fr`, never for `https://shop.brand.example.com`).
     */
    public function findOneByHost(string $host): ?SiteInterface
    {
        $host = strtolower($host);

        if ('' === $host) {
            return null;
        }

        /** @var list<SiteInterface> $candidates */
        $candidates = $this->createQueryBuilder('s')
            // Narrows to the URLs containing the host; the exact host match is checked below.
            // A host holds no `%`; no ESCAPE clause (its default differs between platforms).
            ->andWhere('s.url LIKE :host')
            ->setParameter('host', '%' . $host . '%')
            ->orderBy('s.id', \SortDirection::Ascending)
            ->getQuery()
            ->getResult();

        foreach ($candidates as $site) {
            if ($host === strtolower((string) parse_url((string) $site->getUrl(), \PHP_URL_HOST))) {
                return $site;
            }
        }

        return null;
    }

    /**
     * By id (numeric value) or code.
     */
    public function findOneByIdOrCode(string $value): ?SiteInterface
    {
        return (ctype_digit($value) ? $this->find((int) $value) : null) ?? $this->findOneBy(['code' => $value]);
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
