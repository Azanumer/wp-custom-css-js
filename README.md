# WP Custom CSS & JS

Add site-wide custom CSS and JavaScript from the admin — plus optional per-post CSS/JS — without touching theme files or the Customizer. Everything is stored in the database and printed inline, so there are no extra files to lose when you switch themes.

## Features

- **Site-wide CSS** — printed in `<head>` on every page.
- **Site-wide JavaScript** — printed in the footer by default (or in `<head>` if you need it there).
- **Per-post CSS & JS** — a meta box on every public post type; per-post code prints after the site-wide code, so it can override it.
- **Code editor** — uses WordPress's built-in CodeMirror editor on the settings page when available.
- **No file writes** — survives theme updates and theme switches.
- **Capability-aware** — raw JavaScript can only be saved by users with `unfiltered_html`; other roles get their scripts stripped, so a lower-privileged editor cannot inject code.

## Safety

- CSS is cleaned of `</style>` breakouts; JS is cleaned of `</script>` breakouts.
- The plugin never writes to the filesystem and never touches `wp-config.php` or theme files.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate it from **Plugins**.
3. Go to **Appearance → Custom CSS & JS** for the site-wide code, or open any post for per-post code.

## Notes

- Uninstalling deletes the settings option and all per-post CSS/JS postmeta.
- If you need the same snippet on a single page only, use the per-post box — the site-wide fields are for code that belongs everywhere.

## License

MIT — see [LICENSE](LICENSE).
