# Changelog

All notable changes to this bundle are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Changed

- **BC break** — requires `gingerminds/symfony-core` ^1.10:
  - the bundle extends the core `AbstractResourceBundle`: the site and language admin resources are
    registered on load (below the project `gingerminds_core.resources`), `resources.*.api: false`
    removes an entity from the API;
  - an entity overriding `Site` inherits its `#[ApiResource]` (no copy to keep in sync);
  - `#[Cacheable]`, `#[CacheCascade]`, `#[Sortable]`, `#[Searchable]` and `#[EagerLoad]` on the
    entities instead of the removed core interfaces; `TranslatableTrait` eager loads the
    translations and their language (`getTranslationEagerLoads()` removed, `make:gm:* --translated`
    no longer generates `getEagerLoads()`);
  - `GET /api/sites`: API Platform Doctrine provider and core list contract (`order[code]=asc`,
    `search=`);
  - `#[SiteScoped]` / `#[LanguageScoped]` attributes instead of `SiteScopedInterface` /
    `LanguageScopedInterface`; the context headers are documented by the bundle's own metadata
    factory (the core `HeaderParameterRegistry` is gone);
  - the cache context resolver decorates `gingerminds_core.cache.context_resolver` (no compiler pass);
  - site and language permissions checked by the core `ResourceVoter` (`SiteVoter` / `LanguageVoter`
    removed), `SiteController` / `LanguageController` follow the core `AbstractCrudController`
    (no `CrudContext`);
  - the Google credentials are encrypted by `SiteType` (`setter` of the field) instead of
    `SiteRepository::beforeSave()`.

### Removed

- `SiteQuery` (`getFilters()->disable(SiteFilter::NAME)` and a plain condition instead).

### Fixed

- No `tabindex` on the tab panes of the translations form theme and of the `make:gm:* --translated`
  form template (SonarQube: `tabindex` on non interactive elements).

## [1.2.0] - 2026-10-21

### Added

- `translation_fallback` option (default `true`, as Laravel): `false` removes the fallback language
  from the translations of the API requests (under `gingerminds_core.api.prefix`): a missing
  translation gives `null` fields, the rows stay. The admin and the other requests always fall
  back. `LanguageContext::fallsBack()`.

## [1.1.0] - 2026-10-21

### Added

- Form include slots of the core 1.7 form layout on the site and language forms
  (`site_form_main|sidebar`, `language_form_main|sidebar`) and on the `make:gm:* --translated`
  form template, e.g. for the fields another bundle adds to `SiteType`.

### Changed

- Requires `gingerminds/symfony-core` ^1.7.

## [1.0.0] - 2026-10-05

### Added

- `GingermindsMultisiteBundle` skeleton (`gingerminds_multisite` configuration: overridable
  `site` / `language` resources, `translation` options) and its test application.
- `Site` and `Language` entities (overridable `BaseSite` / `BaseLanguage` mapped superclasses,
  `gingerminds_multisite.resources.*.entity`), `site_language` (`is_default`) and
  `site_front_urls` tables, same schema as `gingerminds/laravel-multisite`.
- Admin CRUD of sites and languages (core resources `site` / `language`, `view|edit|delete
  sites|languages` permissions), in the core "Administration" menu section.
- Google service account credentials encrypted at rest (libsodium, key
  `gingerminds_multisite.translation.encryption_key`, `%kernel.secret%` by default), never
  redisplayed: a blank field keeps them.
- Admin site switcher at the bottom of the sidebar, above the user menu
  (`gingerminds_core.admin_includes.sidebar_bottom`), shown only when there is more than one
  site (`admin_site_id` session key).
- Current site and language: `SiteContext` (admin switcher choice in session, then `X-Site-Id`
  header, request host, first site) and `LanguageContext` (Accept-Language among the site
  languages, site default language as fallback), resolved once per main request or set
  explicitly (`setSite()`, `setLanguages()`); `gm_current_site()` / `gm_current_language()`.
- The core API response cache varies with the current site and language
  (`SiteLanguageCacheContextResolver`).
- `SiteContextedInterface` / `SiteContextedTrait` (`site_id`, current site on creation) and the
  `gingerminds_site` Doctrine filter (current site and shared rows, enabled on HTTP requests);
  `SiteQuery::restrict()` for an explicit site scope.
- `LanguageContextedInterface` / `LanguageContextedTrait` (`<entity>_language` join table) and
  the `gingerminds_language` Doctrine filter.
- `TranslatableInterface` / `TranslatableTrait`, `TranslationInterface` / `TranslationTrait`
  (`<Entity>Translation`, `<owner>_id` + `language_id`, mapped automatically): current
  translation with fallback, `getTranslation()`; `HasTranslatedTitleAndSlugTrait`.

- `TranslationsType` (`translations` of a TranslatableInterface entity): one `entry_type` form
  per language of the current site (every language without site), keyed by language id,
  rendered as tabs (global form theme `@GingermindsMultisite/form/translations_theme.html.twig`).
  The default language is required; an optional language left empty is neither created nor
  validated, and removed when it existed.
- `#[UniqueTranslationSlug]` on a translation entity: slug unique per language and, for a site
  contexted owner, per site (shared rows conflict with every site).
- `GET /api/sites`, public: `id`, `code`, `url`, `languages`, `default_language` (null when
  none), `front_urls`; paginated, sortable, cached per site and language.
- `X-Site-Id` and `Accept-Language` headers documented on the operations of the API resources
  implementing `SiteScopedInterface` / `LanguageScopedInterface` (extended by the site contexted,
  language contexted and translatable interfaces; implemented by `/api/translations`).
- Front translations from a per-site Google Drive xlsx (`translation.enabled`):
  `GET /api/translations` (public; current site or `?site=` id/code; Accept-Language locale
  only when sent), `TranslationService` cached `translation.cache_ttl` seconds (0: until the
  next refresh; a Google error is cached one minute), errors logged to the
  `translation.log_channel` Monolog channel (`var/log/google.log` file handler prepended).
  `TranslationSourceInterface` (`gingerminds_multisite.translation.source`) to read the file
  from elsewhere.
- Admin "Refresh translations" button on the site form (`manage translations` permission,
  synced by `gingerminds:permissions:sync`): `RefreshSiteTranslations` Messenger message,
  handled right away without Messenger.
- `--translated` option of `make:gm:resource`, `make:gm:entity`, `make:gm:form` and
  `make:gm:crud-controller` (with symfony/maker-bundle): translatable entity and its
  `<Name>Translation` entity (`<name>_translations`, no field), `<Name>TranslationType` and a
  `translations` TranslationsType field, admin `_form` template with General / Translations
  tabs (the language tabs in the second, the first invalid tab open).

### Changed

- Documentation: installation, configuration, context, entities & traits, forms, API, front
  translations, commands and the "Coming from laravel-multisite" guide.
- Development: the Makefile runs PHP in the `composer` image (zip extension of PhpSpreadsheet),
  `config.platform.ext-gd` (as the Laravel package); `symfony/doctrine-bridge` and
  `symfony/property-access` required explicitly.

### Fixed

- `LanguageContextedTrait` with an overridden join table name (e.g. the Laravel `language_media`
  pivot): its language column is `language_id`, no longer `language_interface_id`.
