<?php

/**
 * @file
 * Hooks provided by the TOC filter module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the options used to be a table of contents.
 *
 * This hook is called by the TOC filter before a table of contents is
 * initialized.
 *
 * A module could add addition options based on custom attributes/options
 *
 * Setting $options to FALSE will stop a table of contents from being generated.
 *
 * @param array &$options
 *   An associative array containing the [toc] token's options merged with the
 *   the TOC filter setting's options.
 * @param string &$content
 *   The content about to be converted to a table of contents.
 *
 * @ingroup toc_filter_api
 */
function hook_toc_filter_alter(&$options, &$content) {
  // If there is less than five <h2> tags don't generate a table of contents.
  if (substr_count($content, '<h2') < 5) {
    $options = FALSE;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
