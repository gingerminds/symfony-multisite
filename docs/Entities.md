# Entities & traits

## Sites and languages

| Entity | Table | Fields |
|---|---|---|
| `Site` (`BaseSite`, `SiteInterface`) | `sites` | `code` (unique), `url`, `google_drive_file_id`, `google_service_account_credentials` (encrypted, never serialized), timestamps |
| `Language` (`BaseLanguage`, `LanguageInterface`) | `languages` | `iso` (unique, lowercased: `fr`), `label`, timestamps |
| `SiteLanguage` | `site_language` | `site_id`, `language_id`, `is_default` (unique site + language) |
| `SiteFrontUrl` | `site_front_urls` | `site_id`, `url` (unique site + url) |

```php
$site->getLanguages();                 // list<LanguageInterface>
$site->setLanguages([$fr, $en]);       // keeps the default flag of the kept languages
$site->getDefaultLanguage();           // ?LanguageInterface
$site->setDefaultLanguage($fr);        // one of the site languages
$site->getFrontUrlValues();            // list<string>
$site->setFrontUrlValues(['https://www.example.com']);
```

Both are cached by the core API response cache for 24 hours (`site` / `language` cache keys); a
language change also invalidates the sites, which embed their languages. To add fields, override
the entity: see [Configuration](Configuration.md#overriding-the-site-entity).

## Site scoped entities

An entity belonging to one site (`site_id`), or shared by every site (no site):

```php
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedTrait;

#[ORM\Entity]
class Event implements SiteContextedInterface
{
    use SiteContextedTrait; // nullable site_id, deleted with its site
}
```

- **`gingerminds_site` Doctrine filter**, enabled on every HTTP request: queries only return the
  rows of the current site and the shared ones — and nothing without current site (Laravel global
  scope). It also applies to the relations and the admin lists (site switcher).
- A new entity without site gets the current one on persist.
- Cross-site query: `$entityManager->getFilters()->disable(SiteFilter::NAME)`; explicit scope:
  `SiteQuery::restrict($queryBuilder, 'e', $site)` (`e.site = :site OR e.site IS NULL`).
- Not deleted with its site:
  `#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'site', joinColumns: [new ORM\JoinColumn(name: 'site_id', onDelete: 'SET NULL')])])]`.

## Language scoped entities

An entity relevant to some languages only (e.g. a media):

```php
use Gingerminds\MultisiteBundle\Model\LanguageContextedInterface;
use Gingerminds\MultisiteBundle\Model\LanguageContextedTrait;

#[ORM\Entity]
class Media implements LanguageContextedInterface
{
    use LanguageContextedTrait; // many-to-many `languages`, join table media_language (media_id, language_id)
}
```

The **`gingerminds_language` Doctrine filter** only returns the rows attached to the current
language (no restriction without current language). Another join table, e.g. the Laravel
`language_media` pivot (its columns stay `media_id` / `language_id`):
`#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'languages', joinTable: new ORM\JoinTable(name: 'language_media'))])]`.

## Translatable entities

The language dependent fields live in a translation entity, one row per language:

```php
use Gingerminds\CoreBundle\Model\EagerLoadableInterface;
use Gingerminds\MultisiteBundle\Model\HasTranslatedTitleAndSlugTrait;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableTrait;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Gingerminds\MultisiteBundle\Model\TranslationTrait;
use Gingerminds\MultisiteBundle\Validator\UniqueTranslationSlug;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product implements TranslatableInterface, EagerLoadableInterface
{
    use HasTranslatedTitleAndSlugTrait; // getTitle(), getSlug(), getSwitchLang()
    use TranslatableTrait;

    public static function getEagerLoads(): array
    {
        return [...self::getTranslationEagerLoads()]; // translations + their language, no query per row
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'product_translations')]
#[UniqueTranslationSlug]
class ProductTranslation implements TranslationInterface
{
    use TranslationTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $title = '';

    #[ORM\Column(length: 255)]
    private string $slug = '';
}
```

`make:gm:resource Catalog/Product --translated` generates both (see [Commands](Commands.md)).

- **Mapping, automatic** (`TranslatableMetadataListener`): `Product::$translations`
  (one-to-many, cascade, orphan removal) and `ProductTranslation::$translatable` joined on
  `product_id` (cascade delete), `language_id`, unique (`product_id`, `language_id`). The
  translation class is `<Entity>Translation` in the same namespace: override
  `getTranslationEntityClass()` / `getTranslatableEntityClass()` otherwise.
- **Current translation**: `getCurrentTranslation()` returns the translation in the current
  language, else in the fallback language (`null` when neither exists); without language context
  (command), the first one.
- **A given language**: `getTranslation($language)` (entity or id), falling back to the fallback
  language unless `getTranslation($language, fallback: false)`.
- `addTranslation()` / `removeTranslation()`; in a form, use [TranslationsType](Forms.md).

## API documentation

Every API resource implementing `SiteScopedInterface` (site contexted entities) or
`LanguageScopedInterface` (language contexted and translatable entities) documents the
`X-Site-Id` / `Accept-Language` headers: see [API](API.md#context-headers).
