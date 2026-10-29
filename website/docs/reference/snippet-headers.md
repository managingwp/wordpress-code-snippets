---
title: Snippet headers
description: The docblock every snippet carries, the Type vocabulary, and what each Status value means.
---

Each snippet starts with a docblock. The generator reads it to build the README, the
sidebar and every snippet page, so the field names are load-bearing.

```php
<?php
/**
 * Plugin Name: Show Server IP in Admin
 * Description: Shows the server IP address as a notice in the WordPress admin.
 * Version: 1.0.0
 * Type: mu-plugin
 * Status: Complete
 */
```

| Field | Required | Used for |
| ----- | -------- | -------- |
| `Plugin Name` | Yes | The page title, the sidebar label and the README index. |
| `Description` | Yes | The page description and the README index. One sentence, no pipes. |
| `Version` | Yes | The version column. Must start with a digit, for example `1.0.0`. |
| `Type` | Yes | The install location: `plugin`, `theme`, `mu-plugin`, `snippet` or `script`. |
| `Status` | Yes | The status column and emoji. |

Only the leading docblock is read. A `version:` or `status:` string further down the file,
inside code or a comment, is ignored.

## Type

The README and the snippet pages use these values:

* `plugin` - a standalone plugin.
* `theme` - a theme, or a file that belongs in a theme.
* `mu-plugin` - a must-use plugin.
* `snippet` - pasted into a snippet manager, such as WP Codebox.
* `script` - a plain PHP file you include yourself.

## Status

| Status | Emoji | Meaning |
| ------ | ----- | ------- |
| `Complete` | :white_check_mark: | Verified working. |
| `WIP` | :construction: | Partly working, expect changes. |
| `Broken` | :x: | Known broken, kept for reference. |
| `Unknown` | :question: | Not verified, or the header is missing a status. |

Status is matched case-insensitively, so `complete` and `Complete` produce the same
emoji.
