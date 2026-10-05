# Coming from `gingerminds/laravel-multisite`

The bundle keeps the Laravel package logic: sites and languages administered in the admin, the
current site / language of a request, site and language scoped models, translatable models with
fallback, the translations form component, the sites API and the Google Drive front
translations. Same database schema. This page maps each concept; see also the core
[Coming from laravel-core](https://github.com/gingerminds/symfony-core/blob/master/docs/FromLaravel.md).

## Concepts

| Laravel multisite | Symfony bundle | Notes |
|---|---|---|
| `LaravelMultisiteServiceProvider`, `LaravelMultisiteAuthServiceProvider` | `GingermindsMultisiteBundle` + `config/services/*.php` | `gingerminds_multisite.*` service ids. |
| `config/gingerminds-multisite.php` | `gingerminds_multisite:` config | `bin/console config:dump-reference gingerminds_multisite` |
| `ResourceResolver` (`resources.site\|language`) | `gingerminds_multisite.resources.site\|language` (`entity`, `controller`, `form`) | Registered as core resources. |
| `Site`, `Language` models | `Site` / `BaseSite` / `SiteInterface`, `Language` / `BaseLanguage` / `LanguageInterface` | Overridable like the core entities. |
| `site_language` pivot (`is_default`), `SiteFrontUrl` | `SiteLanguage`, `SiteFrontUrl` entities | Same tables and columns. |
| `encrypted:array` cast (`APP_KEY`) | `CredentialsEncryptor` (libsodium, `APP_SECRET`) | The Laravel encrypted credentials cannot be decrypted: paste them again. |
| Migrations shipped by the package | `doctrine:migrations:diff` in the project | Same schema. |
| `SiteController`, `LanguageController`, Blade views | Core `AbstractCrudController` (empty controllers), Twig templates | Administration menu section. |
| `SiteRequest`, `LanguageRequest` | `SiteType`, `LanguageType` + validator constraints | snake_case field names. |
| `SitePolicy`, `LanguagePolicy` | `SiteVoter`, `LanguageVoter` | `view\|edit\|delete sites\|languages`. |
| `PermissionSeeder` | `gingerminds:permissions:sync` | Also creates `manage translations`. |
| `SiteContext`, `LanguageContext` (`scoped`) | same names, reset per request | `id()` / `has()` exist (they were called but missing in Laravel). |
| `SiteContextResolver`, `LanguageContextResolver` | same names | |
| `SiteLanguageCacheContextResolver` (rebinding) | same name, replaces `gingerminds_core.cache.context_resolver` | |
| `ApiHeaderParameterRegistry::register(trait)` | `SiteScopedInterface` / `LanguageScopedInterface` markers | Extended by the context interfaces. |
| `SiteContextedModelTrait` (global scope + `creating`) | `SiteContextedInterface` / `SiteContextedTrait` + `gingerminds_site` Doctrine filter + `prePersist` | |
| `scopeForSite()` | `SiteQuery::restrict()` | |
| `LanguageContextedModelTrait` (global scope) | `LanguageContextedInterface` / `LanguageContextedTrait` + `gingerminds_language` Doctrine filter | |
| `TranslatableModelTrait` (`translations`, `currentTranslation`, `translation()`) | `TranslatableInterface` / `TranslatableTrait` (`getTranslations()`, `getCurrentTranslation()`, `getTranslation()`) | Mapping automatic. |
| `$translationModel` property | `getTranslationEntityClass()` | Default `<Entity>Translation`. |
| `TranslationModelTrait` (`language()`, `isFor()`) | `TranslationInterface` / `TranslationTrait` | `<owner>_id` + `language_id`, as `getForeignKey()`. |
| `HasTranslatedTitleAndSlugTrait` | same name | `getTitle()`, `getSlug()`, `getSwitchLang()`. |
| `syncTranslations()` + `<x-...form.inputs.translations>` | `TranslationsType` | Same payload: `translations[<language id>][<field>]`. |
| `AbstractTranslatableResourceRequest`, `BuildsTranslationAttributesTrait` | `TranslationsType` | Errors shown in the language tab. |
| `HandlesSlugUniquenessTrait` | `#[UniqueTranslationSlug]` | |
| `uniqueCodeRule()` | `#[UniqueEntity(fields: ['site', 'code'])]` | |
| `SiteProvider`, `LanguageProvider`, `SiteStateProcessor` | core `ResourceProvider` | |
| `Translation` model, `TranslationProvider` | `ApiResource\Translation`, `TranslationProvider` | `/api/translations`, same output. |
| `GoogleDriveTranslationClient` | `GoogleDriveTranslationSource` (`TranslationSourceInterface`) | Replaceable. |
| `TranslationService`, `TranslationFileParser`, `TranslationSourceException` | same names | |
| `RefreshSiteTranslationsCache` job | `RefreshSiteTranslations` message + handler | Synchronous without transport. |
| `TranslationController::refresh` + refresh panel | `TranslationRefreshController`, button on the site form | |
| `google` log channel (daily file) | `google` Monolog channel, `var/log/google.log` handler | |

## Behaviour changes worth knowing

- **Admin site list** requires `view sites` (`SitePolicy::viewAny` allowed anyone);
  `GET /api/sites` stays public.
- **Site switcher**: shown in the sidebar when there is more than one site. Its choice
  (`admin_site_id` session) only applies to the admin: the API is stateless.
- **`X-Site-Id`** accepts a site id or code.
- **No request, no context**: commands and workers have no current site (Laravel fell back to the
  first site) and no Doctrine filter; set it with `SiteContext::setSite()`.
- **No language context** (no site): the language filter does not restrict (Laravel read the
  `Accept-Language` header).
- **Current translation** chosen in PHP among the loaded translations (eager loaded with
  `getTranslationEagerLoads()`) instead of an SQL `ORDER BY CASE`.
- **Optional translation emptied** in the form is deleted (Laravel kept it with empty values).
- **Language ISO codes** are stored lowercase.
- **Site `code`** uniqueness is validated by the form (database constraint only in Laravel).
- **Site deletion** deletes its site contexted rows by default (`onDelete: CASCADE`, overridable).
- **Front translations cache**: `cache_ttl` is applied (it was read but unused: the cache never
  expired); a Google error is cached one minute only.
- **API payloads**: snake_case (`default_language`, `front_urls`), `default_language` is an
  object or `null`.
