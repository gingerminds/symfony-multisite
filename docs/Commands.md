# Commands

## `make:gm:* --translated`

With `symfony/maker-bundle`, the core generators get a `--translated` option creating a
[translatable resource](Entities.md#translatable-entities):

```bash
bin/console make:gm:resource Catalog/Product --translated [--api]
```

| Command | With `--translated` |
|---|---|
| `make:gm:resource` | everything below |
| `make:gm:entity` | `Product` implements `TranslatableInterface` (+ eager loaded translations), `ProductTranslation` entity (table `product_translations`, no field) |
| `make:gm:form` | `ProductType` with a `translations` [TranslationsType](Forms.md) field, `ProductTranslationType` |
| `make:gm:crud-controller` | `templates/admin/product/_form.html.twig` with a Translations card (language tabs), `product.field.translations` label |

Then:

```bash
bin/console make:entity 'Catalog\ProductTranslation'   # the translated fields (title, slug...)
# add them to ProductTranslationType, #[UniqueTranslationSlug] for a slug
bin/console doctrine:migrations:diff && bin/console doctrine:migrations:migrate
bin/console gingerminds:permissions:sync
```

## `gingerminds:permissions:sync`

The core command also creates the bundle permissions: `view|edit|delete sites`,
`view|edit|delete languages` and `manage translations` (Laravel `PermissionSeeder`).
