# Fix list styling and blank lines in course and excursion content

- Date: 2026-10-23
- Branch: main
- Commits: pending
- Area: web
- Breaking: no

## Summary

Lists in post content now use the same font size, colour and line height as
paragraphs, and empty paragraphs that the classic editor saves for blank lines
are removed from course and excursion content.

## API changes

none

## Data model changes

none

## Migrations

none

## Behaviour changes

- Ordered lists in `.article-body` had no text styles and fell back to the
  body defaults (1.5rem, grey `#6e6e6e`, weight 300). They now match
  paragraphs.
- Nested list items no longer shrink by 0.8em on every level. The font size
  is set on the top-level `ul`/`ol` and inherited.
- Top-level lists in `.article-body` get the same bottom margin as paragraphs
  (`1vw`, `3vw` on mobile) instead of the `4.5vw` block margin.
- A list that TinyMCE wraps in an `<li style="list-style-type: none;">` is
  indented once instead of twice.
- `the_content` for `course` and `excursion` posts drops paragraphs that hold
  only whitespace, `&nbsp;` or `<br>`. Other post types are unchanged.
  `.article-body > p:empty` is hidden everywhere.

## Action required by other agents

- Deployments must rebuild the theme CSS (`yarn` and
  `./node_modules/.bin/gulp` from the repository root). `style.css` is not in
  git.

## Files

- `src/sss/style.sss`: paragraph and list typography in `.article-body`
- `web/app/themes/rkg-theme/lib/ContentFilter.php`: new component that removes
  empty paragraphs from course and excursion content
- `web/app/themes/rkg-theme/lib/RKGTheme.php`: registers `ContentFilter`
