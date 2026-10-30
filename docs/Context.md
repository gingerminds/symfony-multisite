# Site & language context

Two services answer "what site / language is this request for?": `SiteContext` and
`LanguageContext`. Inject them anywhere; nothing to add to your application.

```php
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;

public function __construct(
    private readonly SiteContext $siteContext,
    private readonly LanguageContext $languageContext,
) {
}

public function __invoke(): Response
{
    $site = $this->siteContext->site();            // ?SiteInterface (also id(), has())
    $current = $this->languageContext->current();  // ?LanguageInterface
    $fallback = $this->languageContext->fallback(); // ?LanguageInterface
    $ids = $this->languageContext->ids();          // [current id, fallback id], best first
}
```

In Twig, the public functions for the front templates:

| Function | Returns |
|---|---|
| `gm_current_site()` | the current site (`?SiteInterface`): `gm_current_site().code`, `.url` |
| `gm_current_language()` | the current language (`?LanguageInterface`): `gm_current_language().iso` |

```twig
<html lang="{{ gm_current_language().iso|default('fr') }}">
```

The other `gm_multisite_*` functions serve the bundle templates (site switcher, translations
refresh) and may change.

Both are resolved once per main request, and reset between requests (`kernel.reset`).

## `SiteContext`

`SiteContextResolver` tries, in order:

1. the admin site switcher choice — `admin_site_id` in session, only when a session already
   exists and the request is not stateless (never on the API, even called with the admin session
   cookie from Swagger UI);
2. the `X-Site-Id` header — a site id or code;
3. a site whose `url` has the request host (the `front_urls` are not used);
4. the first site, or a 404 with `unknown_host: not_found`, except in the admin (usually served on
   a back-office domain), which always falls back to the first site.

Only among the sites of the current user: see [Site access](SiteAccess.md).

## `LanguageContext`

Resolved on the current site (no site: `null` for both):

- **fallback**: the site language flagged as default, else its first language;
- **current**: the `Accept-Language` preferred language enabled on the site
  (`Request::getPreferredLanguage()`: quality values, `fr-FR` matches `fr`), else the fallback.

Out of the admin (`gingerminds_core.admin_prefix`), the current language is also the Symfony
locale: the request one and the translator, Intl and Twig ones (`LocaleSwitcher`). The admin keeps
the language of its interface, the current language being the one of the edited content.

A translatable entity shows its translation in the current language, else in the fallback one
(`ids()`). On the API requests (under `gingerminds_core.api.prefix`), `translation_fallback: false`
removes the fallback: a missing translation gives `null` fields, the rows stay in the collections
(the front decides). The admin, the commands and the other requests always fall back.

```yaml
gingerminds_multisite:
    translation_fallback: false
```

## Outside of a request

Commands and message handlers have no request: no current site nor language (the Doctrine filters
are not enabled either). Set them explicitly when needed:

```php
$this->siteContext->setSite($site);
$this->languageContext->setLanguages($french, $english); // current, fallback (default: current)
```

## Admin site switcher

As soon as there are two sites, the sidebar shows a site switcher right above the user menu
(`gingerminds_core.admin_includes.sidebar_bottom`). The chosen site becomes the current site of
the admin: the [site filter](Entities.md#site-scoped-entities) scopes every list to it, and new
site contexted entities are created on it. Template:
`@GingermindsMultisite/admin/_site_switcher.html.twig` (override it in
`templates/bundles/GingermindsMultisiteBundle/admin/`).

## API response cache

The core API response cache keys vary with the current site and language
(`SiteLanguageCacheContextResolver` replaces the core no-op `gingerminds_core.cache.context_resolver`):
two sites, or two languages, never share a cached response.

## Changing the resolution

Decorate or replace `gingerminds_multisite.context.site_resolver` (extend `SiteContextResolver`,
its `fromSession()` / `fromHeader()` are protected) or `gingerminds_multisite.context.language_resolver`.
