<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Entity\Language;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Repository\Language\LanguageRepository;

#[ORM\Entity(repositoryClass: LanguageRepository::class)]
#[ORM\Table(name: 'languages')]
class Language extends BaseLanguage
{
}
