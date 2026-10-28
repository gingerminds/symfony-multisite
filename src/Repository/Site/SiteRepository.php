<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Repository\Site;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CoreBundle\Repository\AbstractRepository;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Security\SiteAccess;

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
        private readonly SiteAccess $access,
    ) {
        parent::__construct($registry, $entityClass);
    }

    /**
     * The sites of the current user (SiteAccess).
     *
     * @return list<SiteInterface>
     */
    public function findAllOrdered(): array
    {
        /** @var list<SiteInterface> */
        return $this->createScopedQueryBuilder()->orderBy(self::ALIAS . '.code', \SortDirection::Ascending)->getQuery()->getResult();
    }

    /**
     * The first site of the current user (SiteAccess).
     */
    public function findFirst(): ?SiteInterface
    {
        /** @var SiteInterface|null */
        return $this->createScopedQueryBuilder()
            ->orderBy(self::ALIAS . '.id', \SortDirection::Ascending)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
     * Every read of the admin and the API (lists, edit page, switcher): the sites of the
     * current user only.
     */
    protected function configureListQueryBuilder(QueryBuilder $qb): void
    {
        $ids = $this->access->allowedSiteIds();

        if (null === $ids) {
            return;
        }

        if ([] === $ids) {
            $qb->andWhere('1 = 0');

            return;
        }

        $qb->andWhere(self::ALIAS . '.id IN (:gm_allowed_sites)')->setParameter('gm_allowed_sites', $ids);
    }
}
