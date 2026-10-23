# Fix list styling in post content

- Date: 2026-10-23
- Branch: main
- Commits: pending
- Area: web
- Breaking: no

## Summary

Lists in post content now use the same font size, colour and line height as
paragraphs. Blank lines (`<p>&nbsp;</p>` from the classic editor) are kept and
take up one line of text, because lists no longer add a `4.5vw` margin around
them.

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

## Action required by other agents

- Deployments must rebuild the theme CSS (`yarn` and
  `./node_modules/.bin/gulp` from the repository root). `style.css` is not in
  git.

## Files

- `src/sss/style.sss`: paragraph and list typography in `.article-body`
