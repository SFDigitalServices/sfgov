<?php

namespace Drupal\simple_instagram_feed\Services;

/**
 * Defines Simple Instagram Feed Library interface.
 */
interface SimpleInstagramFeedLibraryInterface {

  /**
   * Check library avalilability.
   *
   * @param bool $warning
   *   Add a warning message if library is not available.
   *
   * @return bool
   */
  public function isAvailable(bool $warning = FALSE);

  /**
   * Get the warning message.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getWarningMessage();

}
