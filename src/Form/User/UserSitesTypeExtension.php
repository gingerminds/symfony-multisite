<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Form\User;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Gingerminds\CoreBundle\Form\User\UserType;
use Gingerminds\CoreBundle\Resource\ResourceRegistry;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\SiteRestrictedUserInterface;
use Gingerminds\MultisiteBundle\Security\SiteAccess;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * The sites of a SiteRestrictedUserInterface user in the core user form (`all_sites`,
 * `sites`), rendered by the `user_form_sidebar` include. Only for a super admin, or the
 * holder of `assign sites` on another account: an assigner limited to some sites offers
 * theirs only, keeps the other ones and never grants every site. A new user created by
 * anyone else gets the sites of their creator.
 */
final class UserSitesTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly SiteAccess $access,
        private readonly Security $security,
        private readonly ResourceRegistry $resources,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [UserType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->addFields(...));
        $builder->addEventListener(FormEvents::SUBMIT, $this->inheritCreatorSites(...));
    }

    private function addFields(FormEvent $event): void
    {
        $user = $event->getData();

        if (!$user instanceof SiteRestrictedUserInterface || !$this->access->canAssign($user)) {
            return;
        }

        $allowed = $this->access->allowedSiteIds();

        if (null !== $allowed && null === $user->getId()) {
            // A limited assigner never grants every site.
            $user->setAllSites(false);
        } elseif (null !== $allowed && $user->hasAllSites()) {
            // Every site is beyond a limited assigner.
            return;
        }

        if (null === $allowed) {
            $event->getForm()->add('all_sites', CheckboxType::class, [
                'label' => 'user_sites.field.all_sites',
                'property_path' => 'allSites',
                'required' => false,
                'translation_domain' => 'GingermindsMultisite',
            ]);
        }

        $event->getForm()->add('sites', EntityType::class, [
            'label' => 'user_sites.field.sites',
            'class' => $this->resources->getEntityClass('site'),
            'query_builder' => static function (EntityRepository $repository) use ($allowed): QueryBuilder {
                $qb = $repository->createQueryBuilder('s')->orderBy('s.code');

                return null === $allowed ? $qb : $qb->andWhere('s.id IN (:allowed)')->setParameter('allowed', [] === $allowed ? [0] : $allowed);
            },
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'choice_translation_domain' => false,
            'translation_domain' => 'GingermindsMultisite',
            // Only the sites of the assigner are shown and written: the other ones are kept.
            'getter' => static fn (SiteRestrictedUserInterface $user): array => array_values(array_filter(
                $user->getSites()->toArray(),
                static fn (SiteInterface $site): bool => null === $allowed || \in_array($site->getId(), $allowed, true),
            )),
            'setter' => static function (SiteRestrictedUserInterface $user, iterable $sites) use ($allowed): void {
                $kept = null === $allowed ? [] : array_filter(
                    $user->getSites()->toArray(),
                    static fn (SiteInterface $site): bool => !\in_array($site->getId(), $allowed, true),
                );
                $user->setSites([...$kept, ...$sites]);
            },
        ]);
    }

    private function inheritCreatorSites(FormEvent $event): void
    {
        $user = $event->getData();
        $creator = $this->security->getUser();

        if (
            !$user instanceof SiteRestrictedUserInterface
            || null !== $user->getId()
            || $event->getForm()->has('sites')
            || !$creator instanceof SiteRestrictedUserInterface
        ) {
            return;
        }

        $allowed = $this->access->allowedSiteIds($creator);
        $user->setAllSites(null === $allowed);
        $user->setSites(null === $allowed ? [] : $creator->getSites());
    }
}
