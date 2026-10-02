# Front translations (Google Drive)

The front translations of a site (labels of the front application, not the translations of
your entities) are read "live" from an xlsx spreadsheet on Google Drive, with a cache.

## Setup

1. Enable the feature: `gingerminds_multisite.translation.enabled: true` (see [Installation](Installation.md#5-optional-front-translations-from-google-drive)).
2. Create a Google Cloud service account with the Drive API enabled, download its JSON key, and
   share the spreadsheet (or its shared drive) with the service account email.
3. In the admin, on the site form, **Front translations** card: the Google Drive file id (in the
   URL: `https://docs.google.com/spreadsheets/d/<file id>/edit`) and the JSON key.

The JSON key is encrypted with `APP_SECRET` (`translation.encryption_key`) and never redisplayed:
left blank, the saved credentials are kept. Changing `APP_SECRET` makes them unreadable: paste
the key again.

## Spreadsheet

A native Google Sheet (exported to xlsx) or an uploaded xlsx file. First sheet, first row: a `key`
column, then one column per locale (case and spaces ignored):

| key | fr | en | de |
|---|---|---|---|
| home.title | Accueil | Home | Startseite |
| cart.empty | Panier vide | Empty cart | Leerer Warenkorb |

Rows without key are ignored, empty cells give `""`.

## `GET /api/translations`

Public. One item per locale of the current site (`X-Site-Id`, host…), or of `?site=` (id or code):

```json
{
    "member": [
        {"locale": "fr", "values": {"home.title": "Accueil", "cart.empty": "Panier vide"}},
        {"locale": "en", "values": {"home.title": "Home", "cart.empty": "Empty cart"}}
    ]
}
```

With an `Accept-Language` header, only its primary locale (`fr-FR,en;q=0.8` gives `fr`; none when
the spreadsheet has no such column). Browsers always send it: a front (or Swagger UI) calling
from the browser receives one locale; call without the header (server side, curl) to get them all.
Same behaviour as `gingerminds/laravel-multisite`.

No translation (empty list) when the feature is disabled, the site has no file id or credentials,
or Google Drive fails.

## Cache

The parsed spreadsheet of a site is cached `translation.cache_ttl` seconds (300 by default; `0`:
until the next refresh) in the `gingerminds_multisite.translation_cache` pool (`cache.app`
adapter), to protect the Google API quota. A Google error is cached one minute only.

## Refresh

On the site form (sites configured for the feature), **Refresh translations** (permission
`manage translations`, Super-Admin always) re-downloads the spreadsheet into the cache. It
dispatches the `RefreshSiteTranslations` Messenger message: handled right away without transport
(or without symfony/messenger), in a worker when routed:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            Gingerminds\MultisiteBundle\Message\RefreshSiteTranslations: async
```

## Errors

Google API errors (authentication, quota, network) and unreadable files are logged as warnings
to the `google` Monolog channel (`translation.log_channel`) with the site id — never the
credentials nor the file. With MonologBundle, the bundle adds a `var/log/google.log` handler
(14 rotated files); define your own `google` handlers to route them elsewhere.

## Another source

Replace `gingerminds_multisite.translation.source` (`TranslationSourceInterface::download()`,
returning the path of a temporary xlsx file) to read the spreadsheet from elsewhere.
