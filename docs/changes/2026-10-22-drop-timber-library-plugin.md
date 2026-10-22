# Drop the Timber 1 timber-library plugin from the theme

- Date: 2026-10-22
- Branch: morrigan/fix/timber-library
- Commits: pending
- Area: infra | web
- Breaking: no

## Summary

The theme's own `composer.json` still required the Timber 1
`wpackagist-plugin/timber-library` plugin. When that plugin is installed and
active, it loads Timber 1 instead of the Composer Timber 2, and
`Timber::init()` in `functions.php` fails with "Call to protected method
Timber\Timber::init()", which takes down both the site and wp-admin. The
requirement is removed, and the theme now shows an admin notice instead of the
fatal error when Timber 1 is loaded.

## API changes

none

## Data model changes

none

## Migrations

none

## Behaviour changes

- If Timber older than 2.0 is loaded, the theme no longer calls
  `Timber::init()`. wp-admin shows an error notice naming the loaded version,
  and the front end serves `static/no-timber.html`, the same fallback used
  when Timber is missing.
- `composer install` in `web/app/themes/rkg-theme/` no longer installs
  `timber-library` into `web/app/plugins/`.
- The theme test bootstrap loads Timber from the Bedrock root
  `vendor/autoload.php` instead of a `timber` or `timber-library` plugin.

## Action required by other agents

- Deployments: deactivate and delete `web/app/plugins/timber-library` if it
  exists (`wp plugin deactivate timber-library`, then
  `wp plugin delete timber-library`). Do not run `composer install` inside the
  theme directory. Run it from the repository root only.

## Files

- `web/app/themes/rkg-theme/composer.json`: removed the `timber-library`
  requirement, the wpackagist repository and the plugin installer path
- `web/app/themes/rkg-theme/functions.php`: Timber version check before
  `Timber::init()`
- `web/app/themes/rkg-theme/tests/bootstrap.php`: load the Bedrock autoloader
  instead of a Timber plugin
