# API

API Platform routes, under the API Platform prefix (`/api`). Fields are snake_case.

## `GET /api/sites`

Public (front applications list the sites before any login), paginated
(`itemsPerPage`, `page`), sortable (`sortBy=code&sort=asc`), searchable (`filters[search]`: code, url).

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

To expose more operations or fields, [override the entity](Configuration.md#overriding-the-site-entity)
and restate its `#[ApiResource]`.

## `GET /api/translations`

The front translations of the current site: see [Front translations](Translations.md).

## Context headers

Every API resource implementing one of these marker interfaces documents the matching header
on all of its OpenAPI operations (core `HeaderParameterRegistry`), with no attribute to add:

| Interface | Header | Implemented / extended by |
|---|---|---|
| `SiteScopedInterface` | `X-Site-Id` (site id or code) | `SiteContextedInterface`, `/api/translations` |
| `LanguageScopedInterface` | `Accept-Language` | `LanguageContextedInterface`, `TranslatableInterface`, `/api/translations` |

A DTO API resource whose response depends on the current site or language implements them too.
`GET /api/sites` documents none: the sites list does not depend on the current site.

The API is stateless: the admin site switcher choice (session) is ignored, even when Swagger UI
sends the admin session cookie. Use `X-Site-Id` (or the host) to target a site.
