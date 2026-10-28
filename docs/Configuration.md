# Configuration

Full reference: `bin/console config:dump-reference gingerminds_multisite`.

```yaml
# config/packages/gingerminds_multisite.yaml
gingerminds_multisite:
    resources:
        site:
            entity: ~          # project entity extending BaseSite (null: the bundle one)
            controller: ~      # extends the bundle SiteController
            form: ~            # extends the bundle SiteType
            api: true          # false: no GET /api/sites (inherited by an overriding entity)
        language:
            entity: ~          # extends BaseLanguage
            controller: ~      # extends LanguageController
            form: ~            # extends LanguageType

    translation:               # front translations from Google Drive, see Translations.md
        enabled: false         # master switch: admin fields, API, refresh
        cache_ttl: 300         # seconds; 0: until the next refresh
        log_channel: google    # Monolog channel of the Google API errors
        encryption_key: '%kernel.secret%'   # encrypts the service account credentials

    translation_fallback: true # API only: a missing translation falls back to the site default
                               # language (false: its fields are null), see Context.md
```

## Resources

`site` and `language` are registered as `gingerminds_core` resources (core
`AbstractResourceBundle`, below the project `gingerminds_core.resources.site|language`
configuration, which still overrides any key, e.g. `redirect_after_edit`):

| Key | `site` | `language` |
|---|---|---|
| Admin path | `/admin/sites` | `/admin/languages` |
| Routes | `gingerminds_multisite_site_*` | `gingerminds_multisite_language_*` |
| Permissions | `view\|edit\|delete sites` | `view\|edit\|delete languages` |
| Templates | `@GingermindsMultisite/pages/site/*` | `@GingermindsMultisite/pages/language/*` |
| Translations | `GingermindsMultisite` domain, `site.*` | `language.*` |

Both appear in the core **Administration** menu section (weights 40 and 50, after the core
entries: users 0 … permissions 30).

## Overriding the `Site` entity

As the core entities: extend the mapped superclass, restate the Doctrine class-level attributes, declare it.
The bundle `#[ApiResource]` (`GET /api/sites`) is inherited: restate one only to change it.

```php
// src/Entity/Site/Site.php
namespace App\Entity\Site;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Entity\Site\BaseSite;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'sites')]
class Site extends BaseSite
{
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $brand = null;
}
```

```yaml
gingerminds_multisite:
    resources:
        site:
            entity: App\Entity\Site\Site
```

The bundle then excludes its own `Site` from the Doctrine mapping and gives its API resource to
yours, resolves `SiteInterface` to your class (every relation targets it) and uses it in forms, voters,
repositories and the context. The `site_language` / `site_front_urls` relations are mapped on your
class automatically (`SiteMetadataListener`: Doctrine forbids inverse sides on a mapped superclass).
`Language` works the same way (`BaseLanguage`, `LanguageInterface`).

## Extending the forms

The site and language forms use the core form layout: their columns end with the
`site_form_main` / `site_form_sidebar` and `language_form_main` / `language_form_sidebar`
admin include slots, e.g. for the fields a bundle or a project adds to `SiteType` with a form
type extension. See the core [Forms](https://github.com/gingerminds/symfony-core/blob/master/docs/templating/forms.md#form-layout-and-include-slots)
documentation.

```yaml
# config/packages/gingerminds_core.yaml
gingerminds_core:
    admin_includes:
        site_form_sidebar: ['admin/site/_seo.html.twig']
```

The `make:gm:* --translated` form template uses the same layout.

## Services

Every service has a `gingerminds_multisite.*` id (`bin/console debug:container gingerminds_multisite`):
decorate or replace it as usual. The most useful ones:

| Id | Role |
|---|---|
| `gingerminds_multisite.context.site_resolver` | how the current site is resolved (`SiteContextResolver`) |
| `gingerminds_multisite.context.language_resolver` | how the current language is resolved (`LanguageContextResolver`) |
| `gingerminds_multisite.translation.source` | where the translations xlsx is downloaded from (`TranslationSourceInterface`) |
| `gingerminds_multisite.translation_cache` | cache pool of the front translations (`cache.app` adapter) |
