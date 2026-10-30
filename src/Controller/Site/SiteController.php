<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Controller\Site;

use Gingerminds\CoreBundle\Controller\AbstractCrudController;
use Symfony\Component\Form\FormInterface;

class SiteController extends AbstractCrudController
{
    public function __construct(
        protected readonly bool $translationEnabled = false,
    ) {
    }

    protected function getFormParameters(object $entity, FormInterface $form, bool $isNew): array
    {
        return ['translation_enabled' => $this->translationEnabled];
    }
}
