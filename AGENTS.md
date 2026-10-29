# AGENTS.md

## Development
* Always provide a git commit message using conventional commits format. This is required for the changelog to be generated correctly https://www.conventionalcommits.org/en/v1.0.0/
## Notes
* For type there is only "plugin", "theme", and "mu-plugin".

## Docs

The documentation site lives in `website/` (Docusaurus 3, pinned). Content is in
`website/docs`, served from `/wordpress-code-snippets/`.

### Generated, never hand-edited

`./generate-readme.sh` owns the files below. Anything between a
`BEGIN GENERATED` / `END GENERATED` marker pair is overwritten on the next run; the
whole `website/docs/snippets/` directory and `website/docs/history/changelog.md` are
replaced outright.

| File | Source of truth |
| ---- | --------------- |
| `website/docs/snippets/<category>/<file>.md` | The snippet's docblock header |
| `website/docs/index.md` | Catalogue block from the headers |
| `website/sidebars.ts` | Category sidebar block from the headers |
| `website/docs/history/changelog.md` | Git tags |
| `README.md` | `README-header.md` + the docs index block |
| `CHANGELOG.md` | Full `git log` |

To change what a page says, change the snippet header or the generator - not the page.

### Adding or changing a snippet

1. Add the file under its category folder with the full header (`Plugin Name`,
   `Description`, `Version`, `Type`, `Status`).
2. Run `./generate-readme.sh`.
3. Commit the snippet together with the regenerated files.

### Checks before pushing

```bash
./generate-readme.sh --check                              # generated docs are current
cd website && npm run build                               # broken links and anchors throw
bash scripts/check-docs-site.sh --site website --repo-root .
```

`--check` exits 1 and names the stale files, so a snippet added without its docs fails
the Docs workflow.
