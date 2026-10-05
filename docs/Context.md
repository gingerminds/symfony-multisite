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

In Twig: `gm_current_site()` and `gm_current_language()`.

Both are resolved once per main request, and reset between requests (`kernel.reset`).

## `SiteContext`

`SiteContextResolver` tries, in order:

1. the admin site switcher choice — `admin_site_id` in session, only when a session already
   exists and the request is not stateless (never on the API, even called with the admin session
   cookie from Swagger UI);
2. the `X-Site-Id` header — a site id or code;
3. a site whose `url` contains the request host;
4. the first site.

## `LanguageContext`

Resolved on the current site (no site: `null` for both):

- **fallback**: the site language flagged as default, else its first language;
- **current**: the first `Accept-Language` code (primary subtag, header order: `fr-FR,en;q=0.8`
  gives `fr`, then `en`) enabled on the site, else the fallback.

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
