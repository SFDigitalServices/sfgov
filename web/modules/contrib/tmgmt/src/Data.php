<?php

namespace Drupal\tmgmt;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use \Drupal\Core\Render\Element;

/**
 * All data-related functions.
 */
class Data {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * String used to delimit flattened array keys.
   */
  const TMGMT_ARRAY_DELIMITER = '][';

  /**
   * Configuration constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('tmgmt.settings');
  }

  /**
   * Converts a nested data array into a flattened structure with a combined key.
   *
   * This function can be used by translators to help with the data conversion.
   *
   * Nested keys will be joined together using a colon, so for example
   * $data['key1']['key2']['key3'] will be converted into
   * $flattened_data['key1][key2][key3'].
   *
   * @param array $data
   *   The nested array structure that should be flattened.
   * @param string $prefix
   *   Internal use only, indicates the current key prefix when recursing into
   *   the data array.
   * @param array $label
   *   Label for the data.
   *
   * @return array
   *   The flattened data array.
   */
  public function flatten(array $data, $prefix = NULL, $label = array()) {
    $flattened_data = array();
    if (isset($data['#label'])) {
      $label[] = $data['#label'];
    }
    // Each element is either a text (has #text property defined) or has children,
    // not both.
    if (!empty($data['#text'])) {
      $flattened_data[$prefix] = $data;
      $flattened_data[$prefix]['#parent_label'] = $label;
    }
    else {
      $prefix = isset($prefix) ? $prefix . static::TMGMT_ARRAY_DELIMITER : '';
      foreach (Element::children($data) as $key) {
        $flattened_data += $this->flatten($data[$key], $prefix . $key, $label);
      }
    }
    return $flattened_data;
  }

  /**
   * Converts a flattened data structure into a nested array.
   *
   * This function can be used by translators to help with the data conversion.
   *
   * Nested keys will be created based on the colon, so for example
   * $flattened_data['key1][key2][key3'] will be converted into
   * $data['key1']['key2']['key3'].
   *
   * @param array $flattened_data
   *   The flattened data array.
   *
   * @return array
   *   The nested data array.
   */
  public function unflatten(array $flattened_data) {
    $data = array();
    foreach ($flattened_data as $key => $flattened_data_entry) {
      NestedArray::setValue($data, explode(static::TMGMT_ARRAY_DELIMITER, $key), $flattened_data_entry);
    }
    return $data;
  }

  /**
   * Calculates number of words, which a text consists of.
   *
   * @param string $text
   *
   * @return int
   *   Returns count of words of text.
   */
  public function wordCount($text) {
    // Strip tags in case it is requested to not include them in the count.
    if ($this->config->get('word_count_exclude_tags')) {
      $text = strip_tags($text);
    }
    // Replace each punctuation mark with space.
    $text = str_replace(array('`', '~', '!', '@', '"', '#', '$', ';', '%', '^', ':', '?', '&', '*', '(', ')', '-', '_', '+', '=', '{', '}', '[', ']', '\\', '|', '/', '\'', '<', '>', ',', '.'), ' ', $text);
    // Remove duplicate spaces.
    $text =  trim(preg_replace('/ {2,}/', ' ', $text));
    // Turn into an array.
    $array = ($text) ? explode(' ', $text) : array();
    // How many are they?
    $count = count($array);
    // That is what we need.
    return $count;
  }

  /**
   * Calculates number of HTML tags, which a text consists of.
   *
   * @param string $text
   *
   * @return int
   *   Returns count of tags of text.
   */
  public function tagsCount($text) {
    // Regular expression for html tags.
    $html_reg_exp = '/<.*?>/';

    // Find all tags in the text.
    $count = preg_match_all($html_reg_exp, $text, $matches);

    return $count;
  }

  /**
   * Converts string keys to array keys.
   *
   * There are three conventions for data keys in use. This function accepts each
   * of it an ensures a array of keys.
   *
   * @param array|string $key
   *   The key can be either be an array containing the keys of a nested array
   *   hierarchy path or a string with '][' or '|' as delimiter.
   *
   * @return array
   *   Array of keys.
   */
  public function ensureArrayKey($key) {
    if (empty($key)) {
      return array();
    }
    if (!is_array($key)) {
      if (strstr($key, '|')) {
        $key = str_replace('|', static::TMGMT_ARRAY_DELIMITER, $key);
      }
      $key = explode(static::TMGMT_ARRAY_DELIMITER, $key);
    }
    return $key;
  }

  /**
   * Converts keys array to string key.
   *
   * There are three conventions for data keys in use. This function accepts
   * each of it and ensures a string key.
   *
   * @param array|string $key
   *   The key can be either be an array containing the keys of a nested array
   *   hierarchy path or a string.
   * @param string $delimiter
   *   Delimiter to be use in the keys string. Default is ']['.
   *
   * @return string
   *    Keys string.
   */
  public function ensureStringKey($key, $delimiter = Data::TMGMT_ARRAY_DELIMITER) {
    if (is_array($key)) {
      $key = implode($delimiter, $key);
    }
    return $key;
  }

  /**
   * Array filter callback for filtering untranslatable source data elements.
   *
   * @param array $value
   *   Array of values to filter.
   *
   * @return string
   *    Keys string.
   */
  public function filterData(array $value) {
    return !(empty($value['#text']) || (isset($value['#translate']) && $value['#translate'] === FALSE));
  }

  /**
   * Returns a label for a data item.
   *
   * @param array $data_item
   *   The data item array.
   * @param int $max_length
   *   (optional) Specify the max length that the resulting label string should
   *   be cut to.
   *
   * @return string
   *   A label for the data item.
   */
  public function itemLabel(array $data_item, $max_length = NULL) {
    if (!empty($data_item['#parent_label'])) {
      if ($max_length) {
        // When having multiple label parts, we don't know how long each of them
        // is, truncating each to the same length might result in a considerably
        // shorter length than max length when there are short and long labels.
        // Instead, start with the max length and repeat until the whole string
        // is less than max_length. Remove 4 characters per part to avoid
        // unecessary loops.
        $current_max_length = $max_length - (count($data_item['#parent_label']) * 4);
        do {
          $current_max_length--;
          $labels = array();
          foreach ($data_item['#parent_label'] as $label_part) {
            // If this not the last part, reserve 3 characters for the delimiter.
            $labels[] = Unicode::truncate($label_part, $current_max_length, FALSE, TRUE);
          }
          $label = implode(t(' > '), $labels);
        } while (mb_strlen($label) > $max_length);
        return $label;
      }
      else {
        return implode(t(' > '), $data_item['#parent_label']);
      }
    }
    elseif (!empty($data_item['#label'])) {
      return $max_length ? Unicode::truncate($data_item['#label'], $max_length, FALSE, TRUE) : $data_item['#label'];
    }
    else {
      // As a last resort, fall back to a shortened version of the text. Default
      // to a limit of 50 characters.
      return Unicode::truncate($data_item['#text'], $max_length ? $max_length : 50, FALSE, TRUE);
    }
  }

  /**
   * Flattens and filters data for being translatable.
   *
   * @return array
   *    Returns a filtered array.
   */
  public function filterTranslatable($data) {
    return array_filter($this->flatten($data), array($this, 'filterData'));
  }

}
