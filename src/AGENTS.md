# AGENTS.md

Guidance for AI coding agents working in `src/`.

## Scope

This directory contains source assets compiled by the root `gulpfile.js`.
Generated output is written into:

- `../web/app/themes/rkg-theme/style.css`
- `../web/app/themes/rkg-theme/style-admin.css`
- `../web/app/themes/rkg-theme/style-survey.css`
- `../web/app/themes/rkg-theme/static/site.js`
- `../web/app/plugins/rkg-plugin/js/script.js`

Make source changes here. Never hand-edit generated theme/plugin asset.

## Structure

- `sss/` - SugarSS/PostCSS source styles.
- `sss/style.sss` - public theme CSS entry point.
- `sss/style-admin.sss` - WordPress admin CSS entry point.
- `sss/style-survey.sss` - survey-specific CSS entry point.
- `js/theme/site.js` - public theme JavaScript entry point. Contains a lot of interactive website logic.
- `js/theme/modal.js` - included by `site.js` through `gulp-include`. 
- `js/plugin/script.js` - custom plugin JavaScript entry point. Contains custom global logic.
- `js/plugin/Leaflet.EdgeMarker.js` and `js/plugin/TileLayer.Grayscale.js` -
  Leaflet extensions used by plugin map behavior (libraries).
- `js/mixins/` - PostCSS mixin helpers consumed by the Gulp style tasks. Don't edit.

## Commands

Run from the repository root:

```bash
./node_modules/.bin/gulp
./node_modules/.bin/gulp dev
```

Use the first command to verify changes once. Use `gulp dev` when actively editing assets.

## JavaScript Guidelines

- Follow the root `.eslintrc.json`: 4-space indentation, Airbnb base, browser and jQuery environment.
- Existing code uses jQuery document-ready handlers and WordPress-localized
  globals such as `rkgTheme` and `rkgScript`.
- Keep `/* global ... */` declarations accurate when using localized globals or browser libraries such as Leaflet.
- Preserve the current build style: `site.js` uses `//= include` and
  `//=require` directives for `gulp-include`.
- Do not introduce a new bundler or module system in this directory.
- Avoid broad rewrites of large legacy files. Prefer small extracted functions only when they clearly reduce risk or duplication.

## Style Guidelines

- SugarSS syntax is indentation-sensitive. Keep changes consistent with nearby files.
- Shared variables and mixins belong in `_variables.sss`, `_variables_admin.sss`,
  `_mixins.sss`, and `js/mixins/` as appropriate.
- Keep admin-only styles in `style-admin.sss` and survey-only styles in `style-survey.sss`.
- Verify generated CSS after changing shared variables or mixins because they can affect multiple outputs.

## Testing

- Run `./node_modules/.bin/gulp` after asset changes.
- For interactive behavior, test the affected WordPress page in a browser when a local site is available.
- Check browser console errors for JavaScript changes, especially AJAX, modal, profile menu, course, excursion, and Leaflet map behavior.

