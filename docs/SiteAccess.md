# Site access

By default every user works on every site. A project can restrict its users to some sites:
the admin, the site switcher and the API then only offer those. Roles and permissions stay
global: a user restricted to two sites holds the same permissions on both.

## Enable it

Add the interface and the trait to the project user (the core `User` overridden as usual), then
generate the migration (`all_sites` column, `user_sites` table):

```php
// src/Entity/User/User.php
use Gingerminds\CoreBundle\Entity\User\BaseUser;
use Gingerminds\MultisiteBundle\Model\SiteRestrictedUserInterface;
use Gingerminds\MultisiteBundle\Model\UserSitesTrait;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User extends BaseUser implements SiteRestrictedUserInterface
{
    use UserSitesTrait;
}
```

```bash
bin/console doctrine:migrations:diff && bin/console doctrine:migrations:migrate
bin/console gingerminds:permissions:sync   # creates `assign sites`
```

Without the trait, nothing is restricted.

## Rules

- **All sites** (`all_sites`, true by default, existing users included): every site, present and
  future. Otherwise the `sites` of the user only; a user left without site (e.g. their sites were
  deleted) is denied (403).
- A **super admin** always accesses every site.
- **Current site**: a site outside the user's ones (switcher session, request host) is skipped;
  the first allowed site is used instead. An `X-Site-Id` header naming such a site is a 403.
- **Admin**: the site switcher, the site list and the site edit page (404) only show the user's
  sites. Applied by `SiteRepository::configureListQueryBuilder()`, also to `GET /api/sites` for
  an authenticated user.

## Assigning the sites

The **Sites** card of the user form (`user_form_sidebar` include) is shown to:

- a super admin;
- a user holding the `assign sites` permission, on the other accounts (never their own).

An assigner restricted to some sites only offers theirs, never "All sites", keeps the sites of
the user they do not manage and does not edit the sites of a user who has all sites. A user
created by someone without `assign sites` gets the sites of their creator.

## Filtering the users

`UserSitesTrait` declares `#[Filter]` on `sites`: the user list gets a **Sites** filter
(`/admin/users?sites=3`, `GET /api/users?sites=3`). The users list itself is not restricted to the
sites of the viewer.

Not supported yet: assigning the sites through the users API (`POST` / `PATCH /api/users`).
