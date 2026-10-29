# wordpress-code-snippets

A collection of WordPress PHP snippets, documented one page at a time.

**Docs:** https://managingwp.github.io/wordpress-code-snippets/

<!-- BEGIN docs-index -->
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
