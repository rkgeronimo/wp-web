# AGENTS.md

Guidance for AI coding agents working in `web/app/themes/rkg-theme/`.

## Scope

This is the custom RK Geronimo WordPress theme. It owns presentation, Twig templates, theme assets, theme-side AJAX helpers, and page template controllers.

The theme depends on Timber and the custom `rkg-plugin`. If either is missing, `functions.php` swaps to static fallback pages under `static/`.

## Structure

- `functions.php` - theme bootstrap, `RKGTheme\` autoloader, Timber setup,
  sidebar registration, and legacy AJAX handlers.
- `lib/RKGTheme.php` - main theme coordinator; enqueues theme assets and starts theme components.
- `lib/RKGSite.php` - Timber site/context behavior.
- `lib/Admin/` and `lib/Ajax/` - theme-specific admin and AJAX components.
- Top-level `*.php` files - WordPress template controllers that prepare context
  and render Twig templates.
- `templates/` - Timber/Twig templates and partials.
- `assets/img/` - committed theme images.
- `style.css`, `style-admin.css`, `style-survey.css`, and `static/site.js` -
  generated assets from repository-root `src/`.

## Commands

From the repository root:

```bash
./node_modules/.bin/gulp
```

Run Gulp after changing source assets in `src/`. The generated files in this theme are outputs, not the preferred editing location.

## Theme Guidelines

- Keep page rendering and markup in Twig templates where possible.
- Use PHP template controllers to build context, query data, and select the
  Twig template to render.
- Keep reusable theme setup and hooks in `lib/` classes under the `RKGTheme\` namespace.
- Business rules for courses, excursions, inventory, reports, roles, and users should stay in `rkg-plugin`.
- Preserve Timber 1.x conventions used by this codebase.
- Timber autoescape is disabled in `functions.php`; escape values deliberately
  in PHP/Twig when output may include user-controlled data.
- Be careful when editing `functions.php`: it contains bootstrap code plus legacy AJAX handlers, so unrelated changes can have wide effects.

## Twig Guidelines

- Reuse existing partials in `templates/partial/` and shared layout in
  `templates/base.twig`.
- Keep user-facing Croatian copy consistent with nearby templates.
- Do not move plugin-owned report or admin templates into the theme.
- Check for empty/null context values before rendering optional fields.
- Escape user-controlled values unless they are intentionally trusted HTML.

## Asset Guidelines

- Edit source styles and JavaScript under repository-root `src/`.
- Generated theme outputs are:

```text
style.css
style-admin.css
style-survey.css
static/site.js
```

- After source asset changes, run `./node_modules/.bin/gulp` from the repository root and review generated output only as needed.

## Testing

- Run `./node_modules/.bin/gulp` after asset changes.
- Manually verify affected templates/pages when possible, especially login, profile menu, course pages, excursion pages, news pages, and AJAX forms.

