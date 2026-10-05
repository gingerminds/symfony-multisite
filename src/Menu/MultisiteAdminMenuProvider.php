<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Menu;

use Gingerminds\CoreBundle\Menu\AdminMenuProviderInterface;
use Gingerminds\CoreBundle\Menu\CoreAdminMenuProvider;
use Gingerminds\CoreBundle\Menu\MenuItem;
use Gingerminds\CoreBundle\Resource\ResourceRegistry;
use Gingerminds\CoreBundle\Security\Voter\AbstractResourceVoter;

final readonly class MultisiteAdminMenuProvider implements AdminMenuProviderInterface
{
    private const array ENTRIES = [
        'site' => ['bi-globe2', 40],
        'language' => ['bi-translate', 50],
    ];

    public function __construct(
        private ResourceRegistry $resources,
    ) {
    }

    public function getItems(): iterable
    {
        $children = [];

        foreach (self::ENTRIES as $name => [$icon, $weight]) {
            if (!$this->resources->has($name)) {
                continue;
            }

            $resource = $this->resources->get($name);
            $children[] = new MenuItem(
                $resource->translationKey('name_p'),
                $resource->route('index'),
                icon: $icon,
                permission: AbstractResourceVoter::VIEW,
                permissionSubject: $name,
                translationDomain: $resource->translationDomain,
                weight: $weight,
            );
        }

        // Label, icon and weight come from the core section; used only if the core one is missing.
        yield new MenuItem('menu.administration', icon: 'bi-gear', children: $children, id: CoreAdminMenuProvider::ADMINISTRATION, weight: 100);
    }
}
