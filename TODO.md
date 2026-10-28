# TODO

## Chiffrement des credentials Google avec `#[Encrypted]` (Symfony 8.2)

**Quand :** à la sortie de Symfony 8.2 stable (prévue fin novembre 2026).

**Aujourd'hui :** le JSON du compte de service Google est chiffré par `CredentialsEncryptor`
(sodium `secretbox`, clé `translation.encryption_key`, `%kernel.secret%` par défaut), appelé par
l'option `setter` du champ `google_service_account_credentials` de `SiteType`. Il est stocké dans
la colonne texte `googleServiceAccountCredentials` (`SiteGoogleDriveTrait`).

**Cible :** l'attribut `#[Encrypted]` du composant KeyManagement (bridge
`symfony/doctrine-orm-key-management`) sur la propriété. Doctrine chiffre la valeur à l'écriture
et la déchiffre au chargement, sans type ni service maison.

### À faire

- [ ] Exiger `symfony/*: ^8.2` et `symfony/doctrine-orm-key-management`.
- [ ] `#[Encrypted(...)]` sur `SiteGoogleDriveTrait::$googleServiceAccountCredentials`, et choisir
      la clé : clé maître d'un KMS, ou groupe de clés de données stockées en base.
- [ ] Entités du bundle hors de `src/Entity/` du projet : l'attribut n'y est pas détecté
      automatiquement. Vérifier si le bundle peut déclarer le type lui-même (prepend de
      `doctrine_dbal.types`, voir la PR #66766) ou s'il faut le documenter pour les projets.
- [ ] Colonne binaire : migration de schéma. Pas de reprise des valeurs existantes, à ressaisir
      comme lors du portage Laravel.
- [ ] Supprimer `CredentialsEncryptor`, `CredentialsEncryptionException`, l'option
      `translation.encryption_key` et `ext-sodium` s'il n'est plus utilisé ailleurs.
- [ ] `TranslationService` lit directement le JSON en clair, sans `decrypt()`.
- [ ] `SiteType` garde un champ en écriture seule : `getter` → `null` (jamais réaffiché), et le
      `setter` ne touche à rien quand le champ est vide (il ne fait plus que décoder et réencoder le JSON).
- [ ] Mettre à jour les tests (`CredentialsEncryptorTest`, `SiteAdminTest`, `BundleBootTest`),
      `docs/Translations.md`, `docs/Configuration.md`, `docs/FromLaravel.md` et le CHANGELOG
      (BC break : option supprimée, colonne modifiée).

### Références

- [New in Symfony 8.2: Encrypted Doctrine Columns](https://symfony.com/blog/new-in-symfony-8-2-encrypted-doctrine-columns)
- [New in Symfony 8.2: KeyManagement Component](https://symfony.com/blog/new-in-symfony-8-2-keymanagement-component)
- [symfony/symfony#66806 : attribut `Encrypted`](https://github.com/symfony/symfony/pull/66806)
- [symfony/symfony#66766 : types DBAL chiffrés déclarés par la configuration du bundle](https://github.com/symfony/symfony/pull/66766)
- [symfony/doctrine-orm-key-management](https://github.com/symfony/doctrine-orm-key-management)
