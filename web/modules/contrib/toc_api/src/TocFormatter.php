<?php

/**
 * @file
 * Contains \Drupal\toc_api\TocFormatter.
 */

namespace Drupal\toc_api;

/**
 * Defines a service for formatting a table of content's headers, numbering, and ids..
 */
class TocFormatter implements TocFormatterInterface {

  /**
   * {@inheritdoc}
   */
  public function convertStringToId($text) {
    // Replace accents with their counterparts.
    $text = strtr($text, ['Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r']);

    // Lowercase.
    $text = strtolower($text);

    // Remove apostrophes.
    $text = str_replace("'s ", ' ', $text);

    // Replace non letter or digits by -.
    $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

    // Remove number from beginning.
    // https://css-tricks.com/ids-cannot-start-with-a-number/
    $text = preg_replace('/^\d+/', '-', $text);

    // Trim.
    $text = trim($text, '-');

    // Remove unwanted characters.
    $text = preg_replace('/[^-\w]+/', '', $text);

    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function convertNumberToListTypeValue($number, $type) {
    $case_func = NULL;
    // Check if type should upper or lower cased.
    if (preg_match('/^(upper|lower)-(.+)$/', $type, $match)) {
      $type = $match[2];
      $case_func = 'strto' . $match[1];
    }

    if ($number === 0) {
      return '0';
    }

    switch ($type) {
      case 'roman':
        $value = self::convertNumberToRomanNumeral($number);
        break;

      case 'alpha':
        $value = self::convertNumberToLetter($number);
        break;

      default:
        $value = (string) $number;
        break;
    }

    return ($case_func) ? $case_func($value) : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function convertNumberToRomanNumeral($number) {
    $roman_numerals = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
    $result = '';
    foreach ($roman_numerals as $roman_numeral => $roman_number) {
      $matches = intval($number / $roman_number);
      $result .= str_repeat($roman_numeral, $matches);
      $number = $number % $roman_number;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function convertNumberToLetter($number) {
    static $letters = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
    return $letters[(($number - 1) % 26)];
  }

  /**
   * {@inheritdoc}
   */
  public function convertHeaderKeysToValues(array $header_keys, array $options) {
    if (!empty($options['number_path_truncate'])) {
      // Remove empty numbers from the beginning and end of the $header_keys
      // array but not the middle of it. This is why we can't just use
      // array_filter().
      foreach ($header_keys as $header_tag => $header_number) {
        if ($header_number === 0) {
          unset($header_keys[$header_tag]);
        }
        else {
          break;
        }
      }
      $header_keys = array_reverse($header_keys, TRUE);
      foreach ($header_keys as $header_tag => $header_number) {
        if ($header_number === 0) {
          unset($header_keys[$header_tag]);
        }
        else {
          break;
        }
      }
      $header_keys = array_reverse($header_keys, TRUE);
    }

    $header_parts = [];
    foreach ($header_keys as $header_tag => $header_number) {
      $header_options = $options['headers'][$header_tag];
      $header_parts[$header_tag] = self::convertNumberToListTypeValue($header_number, $header_options['number_type']);
    }
    return $header_parts;
  }

  /**
   * {@inheritdoc}
   */
  public function convertAllowedTagsToArray($allowed_tags) {
    return explode(' ', trim(preg_replace('/(^\s*<|\s*>$|>\s*<)/', ' ', $allowed_tags)));
  }

}
