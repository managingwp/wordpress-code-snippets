<?php
/**
 * enable-core-updates-version-control.php
 * Description: Filters whether the automatic updater should consider a filesystem location to be potentially managed by a version control system.
 * Status: Complete
 */
add_filter( 'automatic_updates_is_vcs_checkout', '__return_false', 1 );