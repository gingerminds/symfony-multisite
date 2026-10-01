# Changelog

All notable changes to this bundle are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- `GingermindsMultisiteBundle` skeleton (`gingerminds_multisite` configuration: overridable
  `site` / `language` resources, `translation` options) and its test application.
- `Site` and `Language` entities (overridable `BaseSite` / `BaseLanguage` mapped superclasses,
  `gingerminds_multisite.resources.*.entity`), `site_language` (`is_default`) and
  `site_front_urls` tables, same schema as `gingerminds/laravel-multisite`.
- Admin CRUD of sites and languages (core resources `site` / `language`, `view|edit|delete
  sites|languages` permissions), in the core "Administration" menu section.
- Google service account credentials encrypted at rest (libsodium, key
  `gingerminds_multisite.translation.encryption_key`, `%kernel.secret%` by default), never
  redisplayed: a blank field keeps them.
- Admin site switcher at the bottom of the sidebar, above the user menu
  (`gingerminds_core.admin_includes.sidebar_bottom`), shown only when there is more than one
  site (`admin_site_id` session key).
