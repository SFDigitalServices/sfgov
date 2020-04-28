<?php

/**
 * @file
 * Hooks related to the entlink module.
 */

/**
 * Allow other modules to alter the excluded CSS selector settings.
 *
 * @param string $cssExclude
 *   Comma separated CSS selectors for links that should be ignored.
 */
function hook_extlink_css_exclude_alter(&$cssExclude) {
  // Add one CSS selector to ignore links that match that.
  $cssExclude .= ', .my-module a.button';
}
