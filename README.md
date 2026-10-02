# Gingerminds Multisite Bundle

Multisite and multi-language bundle for Gingerminds Symfony admin panels, built on
[`gingerminds/symfony-core`](https://github.com/gingerminds/symfony-core) — the Symfony 8
counterpart of `gingerminds/laravel-multisite`:

- `Site` and `Language` entities (overridable), administered in the core admin, with a site
  switcher in the sidebar;
- "the current site / language" of a request (`SiteContext`, `LanguageContext`), varying the
  API response cache;
- Doctrine filters scoping your entities to the current site or language, and traits for
  translatable entities (one translation row per language);
- a translations form type rendered as language tabs, a unique slug constraint;
- `GET /api/sites`, front translations read from a per-site Google Drive spreadsheet
  (`GET /api/translations`);
- a `--translated` option for the `make:gm:*` generators.

Requires PHP 8.4, Symfony 8.1, Doctrine ORM 3, API Platform 4.4 and `gingerminds/symfony-core` ^1.5.

## Installation

```bash
composer require gingerminds/symfony-multisite
```

Then follow [Installation](docs/Installation.md) (bundle, routes, database, permissions).

## Documentation

**Getting started**

- [Installation](docs/Installation.md)
- [Configuration](docs/Configuration.md) — options, overriding the `Site` / `Language` resources.
- [Commands](docs/Commands.md) — `make:gm:* --translated`, permissions.
- [Coming from laravel-multisite](docs/FromLaravel.md) — concept mapping and behaviour changes.

**Multisite & multi-language**

- [Context](docs/Context.md) — current site and language, admin site switcher, cache.
- [Entities & traits](docs/Entities.md) — sites, languages, site / language scoped and translatable entities.
- [Forms](docs/Forms.md) — translations tabs, unique slugs.

**API**

- [API](docs/API.md) — `GET /api/sites`, context headers.
- [Front translations](docs/Translations.md) — Google Drive spreadsheet, `GET /api/translations`, refresh.

## Development

```bash
make install       # composer install (Docker)
make assets        # importmap + sass of the test application
make qa            # phpstan, phpcs, phpunit
make serve         # test application on http://127.0.0.1:8000/admin
```

The test application (`tests/Application`) boots the core and the multisite bundles on SQLite,
with an `Article` (site contexted, translatable), `ArticleTranslation` and `Media` (language
contexted) exercising the filters, translations and forms. The Google Drive source is replaced by
`FakeTranslationSource`. `APP_ENV=test php tests/Application/bin/console ...`.
