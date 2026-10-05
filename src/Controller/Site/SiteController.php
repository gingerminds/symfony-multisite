<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Controller\Site;

use Gingerminds\CoreBundle\Controller\AbstractCrudController;
use Gingerminds\CoreBundle\Controller\CrudContext;
use Symfony\Component\Form\FormInterface;

class SiteController extends AbstractCrudController
{
    public function __construct(
        CrudContext $context,
        protected readonly bool $translationEnabled = false,
    ) {
        parent::__construct($context);
    }

    protected function getResourceName(): string
    {
        return 'site';
    }

    protected function getFormParameters(object $entity, FormInterface $form, bool $isNew): array
    {
        return ['translation_enabled' => $this->translationEnabled];
    }
}
