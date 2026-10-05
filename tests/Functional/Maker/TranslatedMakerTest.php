<?php

declare(strict_types=1);

namespace Gingerminds\MultisiteBundle\Tests\Functional\Maker;

use Composer\Autoload\ClassLoader;
use Gingerminds\CoreBundle\Maker\AbstractResourceMaker;
use Gingerminds\CoreBundle\Maker\MakeEntity;
use Gingerminds\CoreBundle\Maker\MakeForm;
use Gingerminds\CoreBundle\Maker\MakeResource;
use Gingerminds\CoreBundle\Maker\ResourceGenerator;
use Gingerminds\MultisiteBundle\Maker\TranslatedResourceMakerExtension;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

/**
 * `--translated` of the core make:gm:* makers, generating into a temporary project.
 */
final class TranslatedMakerTest extends KernelTestCase
{
    private string $directory;
    private string $namespace;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/gingerminds-multisite-maker-' . bin2hex(random_bytes(4));
        $this->namespace = 'MakerTest' . bin2hex(random_bytes(4));
        new Filesystem()->mkdir($this->directory . '/src');
        $this->loader()->addPsr4($this->namespace . '\\', $this->directory . '/src/');
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->directory);
        parent::tearDown();
    }

    public function testMakeResourceTranslated(): void
    {
        $output = $this->make(MakeResource::class, ['--translated' => true]);

        $entity = $this->file('src/Entity/Catalog/Product.php');
        self::assertStringContainsString('implements ResourceInterface, SearchableInterface, SortableInterface, TimestampableInterface, TranslatableInterface, EagerLoadableInterface, \Stringable', $entity);
        self::assertStringContainsString('    use TranslatableTrait;', $entity);
        self::assertStringContainsString('return [...self::getTranslationEagerLoads()];', $entity);

        $translation = $this->file('src/Entity/Catalog/ProductTranslation.php');
        self::assertStringContainsString("#[ORM\\Table(name: 'product_translations')]", $translation);
        self::assertStringContainsString('class ProductTranslation implements TranslationInterface', $translation);

        $form = $this->file('src/Form/Catalog/ProductType.php');
        self::assertStringContainsString("\$builder->add('translations', TranslationsType::class, [", $form);
        self::assertStringContainsString("'entry_type' => ProductTranslationType::class,", $form);
        self::assertStringContainsString('use Gingerminds\MultisiteBundle\Form\Type\TranslationsType;', $form);
        self::assertStringContainsString('ProductTranslation::class', $this->file('src/Form/Catalog/ProductTranslationType.php'));

        $template = $this->file('templates/admin/product/_form.html.twig');
        self::assertStringContainsString('form_widget(form.translations)', $template);
        self::assertStringContainsString("'product.tab.general'|trans({}, 'admin')", $template);
        self::assertStringContainsString('data-bs-target="#product-pane-{{ tab.id }}"', $template);
        self::assertStringContainsString('translations: Translations', $this->file('translations/admin.en.yaml'));
        self::assertStringContainsString('general: General', $this->file('translations/admin.en.yaml'));
        self::assertStringContainsString('make:entity', $output);

        foreach (['src/Entity/Catalog/Product.php', 'src/Entity/Catalog/ProductTranslation.php', 'src/Form/Catalog/ProductType.php', 'src/Form/Catalog/ProductTranslationType.php'] as $file) {
            exec(\sprintf('%s -l %s 2>&1', \PHP_BINARY, escapeshellarg($this->directory . '/' . $file)), $lint, $status);
            self::assertSame(0, $status, implode("\n", $lint));
        }

        // Composer remembers the classes checked before their generation as missing: load the files.
        require_once $this->directory . '/src/Entity/Catalog/Product.php';
        require_once $this->directory . '/src/Entity/Catalog/ProductTranslation.php';
        self::assertTrue(is_a($this->namespace . '\Entity\Catalog\Product', TranslatableInterface::class, true));
        self::assertTrue(is_a($this->namespace . '\Entity\Catalog\ProductTranslation', TranslationInterface::class, true));
        self::assertSame($this->namespace . '\Entity\Catalog\ProductTranslation', ($this->namespace . '\Entity\Catalog\Product')::getTranslationEntityClass());

        // The generated template compiles with the admin Twig functions and filters.
        self::bootKernel();
        self::getContainer()->get(Environment::class)->createTemplate($template);
    }

    public function testMakeEntityTranslatedOnlyGeneratesTheEntities(): void
    {
        $this->make(MakeEntity::class, ['--translated' => true]);

        self::assertFileExists($this->directory . '/src/Entity/Catalog/ProductTranslation.php');
        self::assertFileDoesNotExist($this->directory . '/src/Form/Catalog/ProductTranslationType.php');
    }

    public function testMakeFormTranslatedOnlyGeneratesTheForms(): void
    {
        $this->make(MakeForm::class, ['--translated' => true]);

        self::assertStringContainsString('TranslationsType::class', $this->file('src/Form/Catalog/ProductType.php'));
        self::assertFileExists($this->directory . '/src/Form/Catalog/ProductTranslationType.php');
        self::assertFileDoesNotExist($this->directory . '/src/Entity/Catalog/ProductTranslation.php');
    }

    public function testWithoutTheOptionNothingIsTranslated(): void
    {
        $this->make(MakeResource::class, []);

        self::assertStringNotContainsString('Translatable', $this->file('src/Entity/Catalog/Product.php'));
        self::assertStringNotContainsString('TranslationsType', $this->file('src/Form/Catalog/ProductType.php'));
        self::assertFileDoesNotExist($this->directory . '/src/Entity/Catalog/ProductTranslation.php');
    }

    /**
     * @param class-string<AbstractResourceMaker> $makerClass
     * @param array<string, bool>                 $options
     */
    private function make(string $makerClass, array $options): string
    {
        $maker = new $makerClass(new ResourceGenerator(), [new TranslatedResourceMakerExtension()]);
        $command = new Command($makerClass::getCommandName());
        $maker->configureCommand($command, new InputConfiguration());

        $input = new ArrayInput(['name' => 'Catalog/Product', ...$options], $command->getDefinition());
        $output = new BufferedOutput();
        $fileManager = new FileManager(new Filesystem(), new AutoloaderUtil(new ComposerAutoloaderFinder($this->namespace)), new MakerFileLinkFormatter(), $this->directory, $this->directory . '/templates');
        $maker->generate($input, new ConsoleStyle($input, $output), new Generator($fileManager, $this->namespace));

        return $output->fetch();
    }

    private function file(string $path): string
    {
        self::assertFileExists($this->directory . '/' . $path);

        return (string) file_get_contents($this->directory . '/' . $path);
    }

    private function loader(): ClassLoader
    {
        foreach (spl_autoload_functions() as $function) {
            if (\is_array($function) && $function[0] instanceof ClassLoader) {
                return $function[0];
            }
        }

        self::fail('No Composer class loader registered.');
    }
}
