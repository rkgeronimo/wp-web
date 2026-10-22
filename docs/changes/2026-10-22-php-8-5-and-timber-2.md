# Upgrade to PHP 8.5 and Timber 2

- Date: 2026-10-22
- Branch: morrigan/update-php
- Commits: pending
- Area: infra | backend | web
- Breaking: yes

## Summary

The app now requires PHP 8.5 (tested on 8.5.12) for both native and Docker
deployments. Timber is upgraded from 1.24 to 2.5 (Twig 2 to Twig 3), and the
theme and `rkg-plugin` are migrated to the Timber 2 API. The Docker database
moves from MySQL 5.7 to MySQL 8.4.

## API changes

none. AJAX actions, admin pages and their request/response fields are
unchanged.

## Data model changes

none

## Migrations

none added. A fresh Docker database seeded from `geronimo_basic.sql` must run
the existing migrations and the WordPress schema update (now in `DOCKER.md`):

```bash
docker compose run --rm wpcli wp core update-db
docker compose run --rm php php database/migrate.php migrate
```

## Behaviour changes

- `composer.json` requires `php >= 8.5`. `composer install` fails on older PHP.
- Dependency versions: `timber/timber` 1.24.2 -> 2.5.1, `twig/twig` 2.x-dev ->
  3.30.0, `roots/wordpress` 6.9.4 -> 6.9.10, `vlucas/phpdotenv` 5.5.0 -> 5.7.0,
  `composer/installers` 2.2.0 -> 2.3.0, `roots/bedrock-autoloader` 1.0.4 ->
  1.1.0, `roots/bedrock-disallow-indexing` 2.0.0 -> 2.1.0,
  `squizlabs/php_codesniffer` 3.7.1 -> 3.13.6. `upstatement/routes`,
  `altorouter/altorouter` and `twig/cache-extension` are no longer installed
  (nothing used them).
- `roots/wp-password-bcrypt` stays at 1.1.0. Version 1.3.0 conflicts with
  WordPress >= 6.8, which hashes passwords with bcrypt itself.
- Docker: PHP image `php:8.5.12-fpm-bookworm` (opcache is built in, no longer
  installed as an extension), wp-cli image `wordpress:cli-php8.5`, database
  image `mysql:8.4` (`--innodb-log-file-size` replaced by
  `--innodb-redo-log-capacity`). An existing MySQL 5.7 `db_data` volume must be
  removed with `docker compose down -v`.
- Timber 2 no longer puts `request` in the context. `RKGSite::getRequest()`
  adds an object with the same `get` and `post` arrays, so
  `$context['request']->get[...]` and `request.get.*` in Twig work as before.
- `Timber::get_user()` returns `null` for deleted users, where Timber 1
  returned an empty user. Participant, student, replacement and e-mail lists
  now skip deleted users instead of showing an empty row. Without the check, a
  deleted user in a course or excursion sign-up would cause a fatal error in
  the admin course and excursion reports under Timber 2.
- Timber 2 does not copy user meta onto the user object. The reservations
  table now checks `metadata_exists()` and `get_user_meta()` for gear
  visibility and sizes.
- Timber 2 does not translate `numberposts` and `category` query args. The
  news page and news block now pass `posts_per_page` and `cat`, so the post
  limits (30 and 6) and the category filter keep working.
- Twig templates use `post.thumbnail`, `post.excerpt`, `menu.items` and
  `item.children` instead of the removed Timber 1 helpers. News tiles used
  `get_thumbnail` and would have lost their background images.
- `page-excursions.php` used `$_GET{'...'}`, which is a parse error since
  PHP 8.0.
- The `wp-login.php?action=register` redirect no longer reads an undefined
  `$_GET['action']`. With `WP_DEBUG_DISPLAY` on, that warning was sent before
  the login cookies and blocked logging in.

## Action required by other agents

- Deployments must run PHP 8.5 with the `mysqli`, `gd`, `zip` and `exif`
  extensions, then run `composer install`.
- Update the separately installed `wp-mail-smtp` plugin (3.6.1 in the local
  setup) to its current release. 3.6.1 emits PHP 8.5 deprecation notices.

## Files

- `composer.json`, `composer.lock`: PHP 8.5 and dependency updates
- `Dockerfile`: `php:8.5.12-fpm-bookworm`, opcache removed from extension list
- `docker-compose.yml`: `mysql:8.4`, `wordpress:cli-php8.5`
- `README.md`, `DOCKER.md`: PHP 8.5 requirement, schema update steps,
  MySQL volume upgrade and `wp db` notes
- `web/app/themes/rkg-theme/functions.php`: `Timber::init()`, autoescape via
  `timber/twig/environment/options`, register redirect fix
- `web/app/themes/rkg-theme/lib/RKGSite.php`: `timber/context` and
  `timber/twig` filters, `Timber::get_menu()`, `getRequest()`, Twig 3
  `StringLoaderExtension`
- `web/app/themes/rkg-theme/*.php`, `lib/Admin/UsersList.php`,
  `lib/Ajax/MembersList.php`: Timber 2 API, null checks for users
- `web/app/themes/rkg-theme/page-news.php`,
  `web/app/plugins/rkg-plugin/lib/News.php`: `posts_per_page` and `cat`
- `web/app/themes/rkg-theme/page-excursions.php`: curly brace array access
- `web/app/themes/rkg-theme/templates/*.twig`,
  `web/app/plugins/rkg-plugin/lib/templates/newsBlockContent.twig`: Timber 2
  template helpers
- `web/app/plugins/rkg-plugin/lib/templates/courseReport.twig`,
  `excursionReport.twig`: call `table.display()` as a method
- `web/app/plugins/rkg-plugin/lib/*.php`, `includes/*.php`: Timber 2 API,
  null checks for users
- `web/app/plugins/rkg-plugin/lib/Tables/Reservations.php`: user meta checks
- `web/app/plugins/rkg-plugin/lib/Calendar.php`: declare `$currentYear`
  (dynamic properties are deprecated since PHP 8.2)
- `web/app/themes/rkg-theme/AGENTS.md`: Timber 2 conventions
