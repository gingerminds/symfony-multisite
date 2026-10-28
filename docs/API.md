# API

API Platform routes, under the API Platform prefix (`/api`). Fields are snake_case.

## `GET /api/sites`

Public (front applications list the sites before any login), paginated
(`itemsPerPage`, `page`), sortable (`order[code]=asc`, `order[url]=desc`), searchable (`search=`: code, url).

```json
{
    "@id": "/api/sites/1",
    "@type": "Site",
    "id": 1,
    "code": "main",
    "url": "https://www.example.com",
    "front_urls": ["https://shop.example.com"],
    "languages": [{"@type": "Language", "id": 1, "iso": "fr", "label": "Français"}],
    "default_language": {"@type": "Language", "id": 1, "iso": "fr", "label": "Français"}
}
```

- `default_language` is `null` (not missing) when the site has none.
- The Google Drive file id and the service account credentials are never exposed.
- Languages are not an API resource (`/api/languages` is a 404): they are embedded in the sites.
  API Platform adds a generated `@id` (`/api/.well-known/genid/…`) to each embedded language in
  JSON-LD; plain `application/json` has none.
- Cached by the core API response cache (24 hours, per site and language), invalidated by any
  site or language change.

An [overriding entity](Configuration.md#overriding-the-site-entity) inherits this resource; restate
its `#[ApiResource]` to expose more operations or fields, `resources.site.api: false` to remove it.

## `GET /api/translations`

The front translations of the current site: see [Front translations](Translations.md).

## Context headers

The API resources depending on the current site or language document the matching header on
all of their OpenAPI operations (`ContextHeaderParametersResourceMetadataCollectionFactory`):

| Header | Resources |
|---|---|
| `X-Site-Id` (site id or code) | `SiteContextedInterface` entities, `#[SiteScoped]` classes (`/api/translations`) |
| `Accept-Language` | `TranslatableInterface` and `LanguageContextedInterface` entities, `#[LanguageScoped]` classes (`/api/translations`) |

A DTO API resource whose response depends on the current site or language gets the
`#[SiteScoped]` / `#[LanguageScoped]` attribute (`Gingerminds\MultisiteBundle\Model`).
`GET /api/sites` documents none: the sites list does not depend on the current site.

The API is stateless: the admin site switcher choice (session) is ignored, even when Swagger UI
sends the admin session cookie. Use `X-Site-Id` (or the host) to target a site.
