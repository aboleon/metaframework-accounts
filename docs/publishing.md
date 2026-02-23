# Publishing Resources

## Publish Tags

| Tag | Command | Destination |
|-----|---------|-------------|
| `mfw-accounts-config` | `vendor:publish --tag=mfw-accounts-config` | `config/mfw-accounts.php` |
| `mfw-user-types` | `vendor:publish --tag=mfw-user-types` | `config/mfw-user-types.php` |
| `mfw-accounts-assets` | `vendor:publish --tag=mfw-accounts-assets` | `public/vendor/mfw-accounts/` |
| `mfw-accounts-views` | `vendor:publish --tag=mfw-accounts-views` | `resources/views/modules/mfw-accounts/` |
| `mfw-accounts-translations` | `vendor:publish --tag=mfw-accounts-translations` | `lang/modules/mfw-accounts/` |

## Config

```bash
php artisan vendor:publish --tag=mfw-accounts-config
```

Publishes the package config file. See [Configuration](configuration.md) for available options.

## User Types Config (`mfw-user-types`)

```bash
php artisan vendor:publish --tag=mfw-user-types
```

Publishes `config/mfw-user-types.php`, which defines the `system/account` user type segregation used by the accounts package and MetaFramework typed-user helpers.

## Assets

```bash
php artisan vendor:publish --tag=mfw-accounts-assets
```

Publishes CSS, JS, and image assets to `public/vendor/mfw-accounts/`. Required for the admin UI to function correctly.

## Views

```bash
php artisan vendor:publish --tag=mfw-accounts-views
```

Publishes all Blade views to `resources/views/modules/mfw-accounts/`. Once published, Laravel loads the application's copy instead of the package copy, allowing full view customisation.

## Translations

```bash
php artisan vendor:publish --tag=mfw-accounts-translations
```

Publishes translation files to `lang/modules/mfw-accounts/`. See [Translations](translations.md) for the file structure and usage.

## Front Account Skeleton

The customer-facing portal (login + dashboard) is an optional skeleton that gets published into your application. Two approaches are available:

### Files only

```bash
php artisan vendor:publish --provider="MetaFramework\\Accounts\\Providers\\AccountsServiceProvider" --tag="mfw-accounts-front"
```

Copies the skeleton files into the application without any further wiring.

### Files + automatic wiring

```bash
php artisan mfw-accounts:front
```

In addition to publishing the files, this command:

1. Publishes `config/mfw-user-types.php` (if not already present).
2. Adds `require __DIR__ . '/account.php';` to `routes/web.php` (if not already present).
3. Ensures `config/auth.php` contains an `account` guard, `accounts` provider, and `accounts` password broker.

See [Front Account](front-account.md) for the full list of published files and auth configuration details.

> **Backward-compatibility alias:** The tag `mfw-account` (without the trailing `s`) is kept for legacy compatibility and maps to the same files.
