---
title: Install a snippet
description: Where a snippet's PHP file goes, depending on its Type header.
---

Every snippet page lists a **Type** and a **Status**. The type decides where the file
goes; the status decides whether you should trust it.

| Type | Where it goes | Notes |
| ---- | ------------- | ----- |
| `mu-plugin` | `wp-content/mu-plugins/` | Always loaded, cannot be deactivated from the admin. Create the folder if it does not exist. |
| `plugin` | `wp-content/plugins/&lt;snippet-name&gt;/` | Give it its own folder and keep the file name as published. |
| `snippet` | WP Codebox, Code Snippets, or any snippet manager | Paste the file contents without the opening `<?php`. |
| `script` | Wherever you include it from | A plain PHP file, not a WordPress entry point on its own. |

## Steps

1. Open the snippet page and use the **Source** link to download the `.php` file.
2. Copy the file to the location above for its type.
3. Check the header for site-specific values you must change, such as `Author URI` or a
   log path.
4. Read the **Status** row: `Complete` means the snippet was verified on a live site,
   `WIP` and `Unknown` mean try it on staging first.

## Removing a snippet

Delete the file. Every snippet gates itself with the usual `defined( 'ABSPATH' ) or die()`
guard, so nothing else is left behind. Snippets that write log files can leave those
behind in the uploads directory or the mu-plugins directory - delete them too.
