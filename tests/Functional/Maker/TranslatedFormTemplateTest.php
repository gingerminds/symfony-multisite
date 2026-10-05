<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional\Maker;

use Gingerminds\MultisiteBundle\Context\SiteContext;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use Gingerminds\MultisiteBundle\Form\Type\TranslationsType;
use Gingerminds\MultisiteBundle\Tests\Application\Entity\Article;
use Gingerminds\MultisiteBundle\Tests\Application\Form\ArticleTranslationType;
use Gingerminds\MultisiteBundle\Tests\Functional\ApiTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Twig\Environment;

/**
 * The `--translated` _form.html.twig skeleton: General / Translations tabs, the
 * first invalid one open.
 */
final class TranslatedFormTemplateTest extends ApiTestCase
{
    private Language $fr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fr = $this->fixtures->language('fr');
        self::getContainer()->get(SiteContext::class)->setSite($this->fixtures->site('alpha', [$this->fr], $this->fr));
    }

    public function testTheGeneralTabIsOpenByDefault(): void
    {
        $crawler = $this->render(null);

        self::assertSame(['product.tab.general', 'product.field.translations'], $crawler->filter('nav .nav-tabs > .nav-link')->each(static fn (Crawler $tab): string => trim($tab->text())));
        self::assertCount(1, $crawler->filter('#product-pane-general.active input[name="form[code]"]'));
        self::assertCount(1, $crawler->filter('#product-pane-translations:not(.active) .gm-translations'));
    }

    public function testTheTranslationsTabIsOpenWhenOnlyTheTranslationsAreInvalid(): void
    {
        $crawler = $this->render(['code' => 'news', 'translations' => [(string) $this->fr->getId() => ['title' => '', 'slug' => '']]]);

        self::assertStringContainsString('active', (string) $crawler->filter('#product-tab-translations')->attr('class'));
        self::assertStringContainsString('text-danger', (string) $crawler->filter('#product-tab-translations')->attr('class'));
        self::assertStringNotContainsString('text-danger', (string) $crawler->filter('#product-tab-general')->attr('class'));
        self::assertCount(1, $crawler->filter('#product-pane-translations.active'));
    }

    public function testTheGeneralTabIsOpenWhenBothAreInvalid(): void
    {
        $crawler = $this->render(['code' => '', 'translations' => [(string) $this->fr->getId() => ['title' => '', 'slug' => '']]]);

        self::assertStringContainsString('active', (string) $crawler->filter('#product-tab-general')->attr('class'));
        self::assertStringContainsString('text-danger', (string) $crawler->filter('#product-tab-general')->attr('class'));
        self::assertStringContainsString('text-danger', (string) $crawler->filter('#product-tab-translations')->attr('class'));
    }

    /**
     * @param array<string, mixed>|null $data submitted data, null for a new form
     */
    private function render(?array $data): Crawler
    {
        $form = self::getContainer()->get(FormFactoryInterface::class)
            ->createBuilder(FormType::class, new Article('new'), ['data_class' => Article::class, 'csrf_protection' => false])
            ->add('code', TextType::class, ['mapped' => false, 'constraints' => [new NotBlank()]])
            ->add('translations', TranslationsType::class, ['entry_type' => ArticleTranslationType::class])
            ->getForm();

        if (null !== $data) {
            $form->submit($data);
        }

        $resource = (object) ['snake' => 'product'];
        ob_start();
        include \dirname(__DIR__, 3) . '/src/Maker/skeleton/twig/_form.tpl.php';
        $source = (string) ob_get_clean();

        $template = self::getContainer()->get(Environment::class)->createTemplate("{% form_theme form '@GingermindsCore/form/theme.html.twig' %}" . $source);

        return new Crawler($template->render(['form' => $form->createView()]));
    }
}
