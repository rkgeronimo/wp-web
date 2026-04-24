# AGENTS.md

Guidance for AI coding agents working in `web/app/plugins/rkg-plugin/`.

## Scope

This is the custom RK Geronimo WordPress plugin. It owns most business logic:
courses, excursions, inventory, reports, custom roles, users, news blocks, custom post types, admin screens, and plugin-side AJAX/data behavior.

The plugin is loaded by `rkg-plugin.php`, which registers a simple `RKGeronimo\` namespace autoloader for `lib/`, loads legacy `includes/` files, and starts `RKGeronimo\RKGeronimo`.

## Structure

- `rkg-plugin.php` - plugin header, autoloader, activation hooks, bootstrap.
- `lib/RKGeronimo.php` - main plugin coordinator; initializes components and
  enqueues plugin assets.
- `lib/*` - namespaced plugin components. Components initialized by
  `RKGeronimo::initComponents()` are expected to expose `init()`.
- `lib/Admin/` - admin menu and admin-page behavior.
- `lib/Helpers/` - shared helper classes.
- `lib/Tables/` - table/report rendering helpers.
- `lib/templates/` and `templates/` - Twig templates used by plugin features.
- `includes/` - older non-namespaced plugin code. Preserve compatibility unless
  a task explicitly migrates it.
- `js/script.js` - generated plugin bundle. Source is `../../../../src/js/plugin/`
  from this directory, or `src/js/plugin/` from the repository root.

## Commands

From the repository root:

```bash
./node_modules/.bin/gulp
php database/migrate.php status
```

Use `composer test` for PHP style checks. Use Gulp when changing plugin
JavaScript source in `src/js/plugin/`.

## PHP Guidelines

- New namespaced plugin classes belong under `lib/` with the `RKGeronimo\` namespace and a path matching the autoloader.
- If a new component should load on every request, add it to
  `RKGeronimo::initComponents()` and implement `init()`.
- Use WordPress APIs for hooks, capabilities, nonces, sanitization, escaping, redirects, and AJAX responses.
- Use `$wpdb->prepare()` for dynamic SQL. Use `$wpdb->prefix` for custom table
  names.
- Keep business logic in plugin classes, not theme templates.
- Be careful with Croatian user-facing strings. Preserve existing terminology unless the task asks for wording changes.

## Database Guidelines

- Plugin features use custom tables for courses, excursions, reservations, inventory, and related reports.
- Schema changes require migrations in `../../../../database/migrations/` from this directory, or `database/migrations/` from the repository root.
- Prefer PHP migrations with both `up` and `down` callbacks.
- After schema changes, verify with:

```bash
php database/migrate.php status
php database/migrate.php migrate
```

Run those commands from the repository root.

## Security Notes

- This plugin handles member data, medical/liability reports, course signups, excursion reservations, and inventory reservations. Treat all exported and displayed data as sensitive.
- Check permissions for admin pages, reports, exports, and write actions.
- Validate and sanitize `$_POST`, `$_GET`, AJAX input, and uploaded data before
  use.
- Escape output in templates and admin HTML unless the value is intentionally trusted HTML.
- Do not log personal data, medical data, OIB values, credentials, or tokens.

## Testing

- For JavaScript behavior, edit source under `src/js/plugin/` and run
  `./node_modules/.bin/gulp`.
- Manually verify affected WordPress flows when possible: course signup, excursion signup/waiting list, admin reports, inventory reservations, and map behavior.
