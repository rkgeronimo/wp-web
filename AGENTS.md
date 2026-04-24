# AGENTS.md

Guidance for AI coding agents working in this repository.

## Project Overview

This repository contains the RK Geronimo WordPress application.
The app is based on Roots Bedrock and keeps WordPress core under `web/wp/`,
application code under `web/app/`, and configuration under `config/`.

Primary custom code areas:

- `web/app/themes/rkg-theme/` - custom Twig WordPress theme.
- `web/app/plugins/rkg-plugin/` - custom RK Geronimo business logic, admin screens, reports, courses, excursions, inventory, roles, and users.
- `web/app/plugins/rkg-gallery/` - Gutenberg block plugin that's used in WordPress for excursion and course posts to upload and add multiple photos.
- `src/` - source JS and CSS assets compiled by Gulp into the theme/plugin.
- `database/` - custom database migration tool and migrations.
- `backup/` and `backup_script.py` - Python custom backup system, run occasionally outside WordPress.

More specific agent guidance exists in:

- `src/AGENTS.md`
- `web/app/plugins/rkg-plugin/AGENTS.md`
- `web/app/themes/rkg-theme/AGENTS.md`

Run commands from the repository root unless noted otherwise.

## Build and Test Commands

Install dependencies:

```bash
yarn
composer install
```

Build theme and plugin assets:

```bash
./node_modules/.bin/gulp
```

Watch assets during development:

```bash
./node_modules/.bin/gulp dev
```

Run database migrations:

```bash
php database/migrate.php status
php database/migrate.php migrate
php database/migrate.php rollback
```

Create a migration:

```bash
php database/migrate.php create add_descriptive_name
```

Docker local setup:

Explained in [DOCKER.md](DOCKER.md).

Gutenberg gallery plugin:

```bash
cd web/app/plugins/rkg-gallery
npm run build
npm start
```

Backup script dry run:

```bash
python3 backup_script.py --dry-run --verbose
```

## Code Style Guidelines

- Follow existing WordPress, Bedrock, Timber, and project conventions.
- PHP style is based on PSR-2 plus PEAR commenting rules, no short array syntax,
  debug/version-control sniff checks, and an 85-character soft line limit with
  a 120-character absolute limit.
- JavaScript under the main app follows `.eslintrc.json`.
- Source asset changes should usually be made in `src/`, then rebuilt with Gulp. Do not hand-edit generated files.
- Theme presentation belongs in Twig templates and theme classes. Business logic for courses, excursions, inventory, reports, roles, and users belongs in `rkg-plugin`.

## Testing Instructions

- Always run the narrowest relevant verification after a change.
- For PHP changes, there are currently no available tests and test suites setup.
- For asset changes, run `./node_modules/.bin/gulp` from the repository root.
- For database schema changes, add a migration under `database/migrations/`
  and verify with `php database/migrate.php status` and, where possible,
  `php database/migrate.php migrate` against a local database.
- For backup changes, run `python3 backup_script.py --dry-run --verbose` before any real backup or upload.

## Database Guidelines

- Import `geronimo_basic.sql` directly for initial local setup.
- Use the custom migration tool for schema changes after initial setup.
- Prefer PHP migrations with both `up` and `down` callbacks so changes can be rolled back.
- Keep migrations small and descriptive.
- Use `$wpdb->prefix` for table names in migrations and plugin code.
- Do not modify production-like data directly in code paths or migrations without a clear rollback and verification plan.

## Commit Messages

- Use Conventional Commits: `<type>[optional scope]: <description>`.
- Prefer lowercase types such as `feat`, `fix`, `docs`, `style`, `refactor`,
  `perf`, `test`, `build`, `ci`, and `chore`.
- Use a scope when it adds useful context, for example
  `fix(rkg-plugin): prevent duplicate excursion signup` or
  `docs(agents): add plugin guidance`.
- Use `feat` for user-visible features and `fix` for bug fixes.
- Mark breaking changes with `!` in the header, such as
  `feat(database)!: change course signup schema`, or with a footer starting `BREAKING CHANGE:`.
- Keep the subject short, imperative, and specific. Add a body when the reason or migration/deployment impact is not obvious.

## Security Considerations

- Never commit `.env`, real salts, database credentials, Dropbox credentials, production URLs, or backup tokens.
- Treat `web/app/uploads/`, SQL dumps, backups, and exported reports as sensitive because they may contain user or member data.
- Use WordPress capability checks, nonces, escaping, and sanitization for admin forms, AJAX handlers, shortcodes, and template output.
- Avoid logging secrets, personal data, medical data, reservation details, or course signup data.
- Keep backup changes conservative: dry-run first, preserve the retention safety behavior, and avoid deleting local or remote backups unless explicitly intended.
- Do not bypass dependency conflict warnings without understanding the impact.

## Agent Workflow Notes

- This file lives at the repository root.
- Read existing implementation patterns before adding abstractions.
- Keep changes scoped to the requested behavior and avoid unrelated formatting churn.
- Do not overwrite generated assets, lockfiles, uploads, or environment files unless the task explicitly requires it.
- If local shell startup fails with an `nvm` npm prefix warning, rerun commands without loading shell startup files.
