---
title: Add a snippet
description: The steps for adding a snippet so its docs, sidebar entry and README row appear without hand editing.
---

Snippet pages, sidebar entries, the landing catalogue, the README and the changelog are
all generated. Nothing is hand-written twice.

## Steps

1. Put the file in the right category folder, for example `debug/my-new-snippet.php`.
   Categories are the top-level folders; create a new one when no folder fits.
2. Give it the full header from [snippet headers](../reference/snippet-headers.md). A
   missing field shows up as a generator warning.
3. Regenerate:

   ```bash
   ./generate-readme.sh
   ```

4. Check what changed and commit the snippet together with the generated files:

   ```bash
   ./generate-readme.sh --check
   git status --short
   ```

5. Preview the site:

   ```bash
   cd website && npm start
   ```

## What the generator writes

| File | Content |
| ---- | ------- |
| `README.md` | The docs index table between the `docs-index` markers. |
| `CHANGELOG.md` | The commit history. |
| `website/docs/snippets/<category>/<file>.md` | One page per snippet. |
| `website/docs/index.md` | The catalogue between the `catalogue` markers. |
| `website/docs/history/changelog.md` | The changelog page. |
| `website/sidebars.ts` | The category list between the `snippets` markers. |

These files carry `BEGIN GENERATED` / `END GENERATED` markers. Editing inside the
markers is pointless: the next run overwrites it. To change what a page says, change the
snippet header or the generator.

## Checks before you push

```bash
./generate-readme.sh --check                              # generated files are current
cd website && npm run build                               # broken links and anchors throw
bash scripts/check-docs-site.sh --site website --repo-root .   # the publishable gate
```

`--check` regenerates into a temporary directory and diffs, so CI fails when a snippet is
added without its docs.
