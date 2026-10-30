<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Application\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Article;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Media;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * ContextTest: what a front request sees through the site/language filters.
 */
final readonly class ContentController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SiteContext $siteContext,
        private LanguageContext $languageContext,
        private LocaleAwareInterface $translator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $articles = $this->entityManager->getRepository(Article::class)->findBy([], ['id' => 'ASC']);
        $medias = $this->entityManager->getRepository(Media::class)->findBy([], ['id' => 'ASC']);

        return new JsonResponse([
            'site' => $this->siteContext->site()?->getCode(),
            'language' => $this->languageContext->current()?->getIso(),
            'fallback' => $this->languageContext->fallback()?->getIso(),
            'locale' => [$request->getLocale(), $this->translator->getLocale()],
            'articles' => array_map(static fn (Article $article): array => [
                'code' => $article->getCode(),
                'title' => $article->getTitle(),
                'switch_lang' => $article->getSwitchLang(),
            ], $articles),
            'medias' => array_map(static fn (Media $media): string => $media->getName(), $medias),
        ]);
    }
}
