# wordpress-code-snippets

A collection of WordPress PHP snippets, documented one page at a time.

**Docs:** https://managingwp.github.io/wordpress-code-snippets/

<!-- BEGIN docs-index -->
50 snippets in 21 categories, one page each.

| Section | Where |
| ------- | ----- |
| Snippet catalogue | https://managingwp.github.io/wordpress-code-snippets/ |
| Install a snippet | https://managingwp.github.io/wordpress-code-snippets/getting-started/install-a-snippet |
| Snippet headers | https://managingwp.github.io/wordpress-code-snippets/reference/snippet-headers |
| Add a snippet | https://managingwp.github.io/wordpress-code-snippets/contributing/add-a-snippet |
| Changelog | https://managingwp.github.io/wordpress-code-snippets/history/changelog |
<!-- END docs-index -->

## Quick start

1. Open the docs and pick a snippet.
2. Copy its `.php` file into your site:
   * `mu-plugin` → `wp-content/mu-plugins/`
   * `plugin` → `wp-content/plugins/<name>/`
   * `snippet` → WP Codebox or another snippet manager
3. Read the snippet page before deploying: it lists the version, the type and the status.

## Status legend

| Status | Meaning |
| ------ | ------- |
| :white_check_mark: Complete | Verified working. |
| :construction: WIP | Partly working, expect changes. |
| :x: Broken | Known broken, kept for reference. |
| :question: Unknown | Not verified, or the header has no status. |

## Contributing

Every snippet carries a header (`Plugin Name`, `Description`, `Version`, `Type`,
`Status`). Add or change a snippet, then regenerate the docs and commit both:

```bash
./generate-readme.sh
```

Commit messages use [conventional commits](https://www.conventionalcommits.org/en/v1.0.0/).
Full guide: https://managingwp.github.io/wordpress-code-snippets/contributing/add-a-snippet
