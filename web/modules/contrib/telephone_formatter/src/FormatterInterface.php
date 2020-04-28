<?php

namespace Drupal\telephone_formatter;

/**
 * Formatter service interface.
 *
 * @package Drupal\telephone_formatter
 */
interface FormatterInterface {

  /**
   * Formats telephone number into massaged one based on predefined format.
   *
   * @param string $input
   *   Input phone number.
   * @param string $format
   *   Format option.
   * @param null|string $region
   *   Country code.
   *
   * @return string
   *   Formatted string.
   */
  public function format($input, $format, $region = NULL);

}
