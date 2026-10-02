# Forms

## Translations as language tabs

`TranslationsType` edits the `translations` of a [translatable entity](Entities.md#translatable-entities):
one `entry_type` form per language, rendered as tabs.

```php
use Gingerminds\MultisiteBundle\Form\Type\TranslationsType;

$builder->add('translations', TranslationsType::class, [
    'entry_type' => ProductTranslationType::class, // data_class: ProductTranslation
]);
```

- **Languages**: those of the current site (every language without site); option `languages`
  to pass others, `default_language` for the required one (default: the site default language).
- **The default language is required**: its tab has a `*`, its fields are required and always
  validated (`#[Assert\NotBlank]` etc. on the translation entity).
- **An optional language left empty** is neither created nor validated; an existing translation
  emptied is removed. A language started is validated as the default one.
- **Payload** (admin and API), keyed by language id, as the Laravel form component:
  `translations[<language id>][title]`.
- **Rendering**: global form theme `@GingermindsMultisite/form/translations_theme.html.twig`, no
  configuration. The tab of a language with errors is red with an alert icon, the first invalid tab
  is open. In a custom template: `{{ form_widget(form.translations) }}`.

## Unique slugs

On the translation entity:

```php
#[UniqueTranslationSlug]                       // field `slug`
#[UniqueTranslationSlug(field: 'path')]        // another field
class ProductTranslation implements TranslationInterface
```

A slug is unique per language and, when the owner is [site contexted](Entities.md#site-scoped-entities),
per site (the owner site, else the current one); a shared row (no site) conflicts with every site.
Message: `gingerminds_multisite.translation.slug_not_unique` (`validators` domain).

## Unique per site

Laravel `uniqueCodeRule()` is the standard constraint on a site contexted entity:

```php
#[UniqueEntity(fields: ['site', 'code'])]
class Event implements SiteContextedInterface
```

## Site form

`SiteType` (`gingerminds_multisite.resources.site.form` to replace it) field names are snake_case,
as the API: `code`, `url`, `front_urls` (one URL per line), `languages`, `default_language` (one of
the selected languages), and with `translation.enabled`: `google_drive_file_id`,
`google_service_account_credentials` (JSON, encrypted, never redisplayed: left blank, the saved
credentials are kept).
