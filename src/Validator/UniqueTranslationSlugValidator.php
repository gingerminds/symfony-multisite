<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UniqueTranslationSlugValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SiteContext $siteContext,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueTranslationSlug) {
            throw new UnexpectedTypeException($constraint, UniqueTranslationSlug::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof TranslationInterface) {
            throw new UnexpectedValueException($value, TranslationInterface::class);
        }

        $slug = PropertyAccess::createPropertyAccessor()->getValue($value, $constraint->field);
        $language = $value->getLanguage();

        if (!\is_string($slug) || '' === $slug || !$language instanceof LanguageInterface) {
            return;
        }

        if ($this->isTaken($value, $constraint->field, $slug)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($slug))
                ->setTranslationDomain('validators')
                ->atPath($constraint->field)
                ->addViolation();
        }
    }

    private function isTaken(TranslationInterface $translation, string $field, string $slug): bool
    {
        $class = $translation::class;
        $manager = $this->doctrine->getManagerForClass($class);

        if (!$manager instanceof EntityManagerInterface) {
            return false;
        }

        $qb = $manager->createQueryBuilder()
            ->select('COUNT(t)')
            ->from($class, 't')
            ->andWhere(\sprintf('t.%s = :slug', $field))
            ->andWhere('t.language = :language')
            ->setParameter('slug', $slug)
            ->setParameter('language', $translation->getLanguage());

        if (null !== $manager->getUnitOfWork()->getSingleIdentifierValue($translation)) {
            $qb->andWhere('t != :self')->setParameter('self', $translation);
        }

        $owner = $translation->getTranslatable();

        if ($owner instanceof SiteContextedInterface) {
            $site = $owner->getSite() ?? $this->siteContext->site();

            if ($site instanceof SiteInterface) {
                $qb->join('t.translatable', 'o')
                    ->andWhere('o.site = :site OR o.site IS NULL')
                    ->setParameter('site', $site);
            }
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
