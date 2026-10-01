# Gingerminds Multisite Bundle

Multisite and multi-language bundle for Gingerminds Symfony admin panels, built on
[`gingerminds/symfony-core`](https://github.com/gingerminds/symfony-core) — the Symfony 8
counterpart of `gingerminds/laravel-multisite`.

Requires PHP 8.4, Symfony 8.1, Doctrine ORM 3, API Platform 4.4 and `gingerminds/symfony-core` ^1.5.

> Work in progress: the port of `gingerminds/laravel-multisite` is done step by step,
> see [CHANGELOG](CHANGELOG.md).

## Development

```bash
make install     # composer install (Docker)
make assets      # importmap + sass of the test application
make qa          # phpstan, phpcs, phpunit
```

The test application (`tests/Application`) boots the core and the multisite bundles on SQLite:
`APP_ENV=test php tests/Application/bin/console ...`.
