<?php

/**
 * @file
 * Contains \Drupal\toc_api\TocFormatterInterface.
 */

namespace Drupal\toc_api;

/**
 * Provides an interface defining a TOC formatter.
 */
interface TocFormatterInterface {

  /**
   * Convert a string to a valid HTML id.
   *
   * Notes:
   *   At some point, D8 core or contrib (ie Drupal\pathauto\AliasCleaner) will
   *   provide a service to slugify strings based on predefined options.
   *
   * Inspired by:
   * - PHP function to make slug (URL string)
   *   http://stackoverflow.com/questions/2955251
   * - Replacing accents with their counterparts
   *   http://stackoverflow.com/questions/3230012
   *
   * @param string $text
   *   String to be converted to a valid HTML id.
   *
   * @return string
   *   A valid HTML id.
   */
  public function convertStringToId($text);

  /**
   * Convert a number to a selected type (alpha or roman).
   *
   * References:
   * - CSS list-style-type Property
   *   http://www.w3schools.com/cssref/pr_list-style-type.asp
   *
   * @param int $number
   *   A number.
   * @param string $type
   *   The HTML5 list-style-type.
   *
   * @return string
   *   The number converted to a selected type numeral.
   */
  public function convertNumberToListTypeValue($number, $type);

  /**
   * Convert a number to a roman numeral.
   *
   * References
   * - Convert Numbers To Roman Numerals
   *   http://www.phpro.org/examples/Convert-Numbers-To-Roman-Numerals.html
   *
   * @param int $number
   *   A number.
   *
   * @return string
   *   The number converted to a roman numeral.
   */
  public function convertNumberToRomanNumeral($number);

  /**
   * Convert a number to a letter.
   *
   * References:
   * - Transform the numbers to letters using php
   *   http://stackoverflow.com/questions/18185642
   *
   * @param int $number
   *   A number.
   *
   * @return string
   *   The number converted to a letter.
   */
  public function convertNumberToLetter($number);

  /**
   * Convert headers keyed by number to list type values.
   *
   * @param array $header_keys
   *   An associate array of header tag/number pairs.
   * @param array $options
   *   A TOC objects associative array of options.
   *
   * @return array
   *   An associate array of header tag/value pairs.
   */
  public function convertHeaderKeysToValues(array $header_keys, array $options);

  /**
   * Convert allowed tags string to an array of allowed tags for #markup.
   *
   * @param string $allowed_tags
   *   A string of allowed tags with or without angle brackets.
   *
   * @return array
   *   An array containing allowed tag names.
   */
  public function convertAllowedTagsToArray($allowed_tags);

}
