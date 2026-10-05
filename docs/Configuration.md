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
        language:
            entity: ~          # extends BaseLanguage
            controller: ~      # extends LanguageController
            form: ~            # extends LanguageType

    translation:               # front translations from Google Drive, see Translations.md
        enabled: false         # master switch: admin fields, API, refresh
        cache_ttl: 300         # seconds; 0: until the next refresh
        log_channel: google    # Monolog channel of the Google API errors
        encryption_key: '%kernel.secret%'   # encrypts the service account credentials
```

## Resources

`site` and `language` are registered as `gingerminds_core` resources (prepended: the project
`gingerminds_core.resources.site|language` configuration still overrides any key, e.g.
`redirect_after_edit`):

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

As the core entities: extend the mapped superclass, restate the class-level attributes, declare it.

```php
// src/Entity/Site/Site.php
namespace App\Entity\Site;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Entity\Site\BaseSite;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'sites')]
#[ApiResource(
    shortName: 'Site',
    operations: [new GetCollection(normalizationContext: ['groups' => [BaseSite::GROUP_LIST], 'skip_null_values' => false])],
    paginationClientItemsPerPage: true,
    provider: 'gingerminds_multisite.api.provider.site',
)]
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

The bundle then excludes its own `Site` from the Doctrine mapping and the API resources,
resolves `SiteInterface` to your class (every relation targets it) and uses it in forms, voters,
repositories and the context. The `site_language` / `site_front_urls` relations are mapped on your
class automatically (`SiteMetadataListener`: Doctrine forbids inverse sides on a mapped superclass).
`Language` works the same way (`BaseLanguage`, `LanguageInterface`).

## Services

Every service has a `gingerminds_multisite.*` id (`bin/console debug:container gingerminds_multisite`):
decorate or replace it as usual. The most useful ones:

| Id | Role |
|---|---|
| `gingerminds_multisite.context.site_resolver` | how the current site is resolved (`SiteContextResolver`) |
| `gingerminds_multisite.context.language_resolver` | how the current language is resolved (`LanguageContextResolver`) |
| `gingerminds_multisite.translation.source` | where the translations xlsx is downloaded from (`TranslationSourceInterface`) |
| `gingerminds_multisite.translation_cache` | cache pool of the front translations (`cache.app` adapter) |
