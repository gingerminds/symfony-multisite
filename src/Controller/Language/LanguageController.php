<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Controller\Language;

use Gingerminds\CoreBundle\Controller\AbstractCrudController;

class LanguageController extends AbstractCrudController
{
    protected function getResourceName(): string
    {
        return 'language';
    }
}
