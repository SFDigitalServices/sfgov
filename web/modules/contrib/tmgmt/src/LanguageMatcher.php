<?php

namespace Drupal\tmgmt;

/**
 * Language matcher service.
 */
class LanguageMatcher {

  /**
   * Return the better match of a langcode over a list of langcodes.
   *
   * Use partial matching to return the better match of a local language
   * langcode over a list of remote languages langcodes.
   *
   * We can have one of this three cases:
   * - In case of a exact match it will just return the match.
   * - In case of a partial match it will return the first partial
   * match sorted alphabetically.
   * - In case there is no partial match neither, it will just return the local
   * language.
   *
   * @param string $local_language
   *   The langcode of the local language.
   * @param array $remote_languages
   *   The list of remote languages, the keys must be the remote langcodes.
   *
   * @return string
   *   The best match for the local language.
   */
  public function getMatchingLangcode($local_language, array $remote_languages) {
    $parts = explode('-', $local_language);
    foreach ($parts as $key => $part) {
      $langcode = implode('-', array_slice($parts, 0, count($parts) - $key));

      // Return exact match.
      if (array_key_exists($langcode, $remote_languages)) {
        return $langcode;
      }
      $matches = array_filter(array_keys($remote_languages), function ($key) use ($langcode) {
        return empty($langcode) ? FALSE : strpos($key, $langcode) === 0;
      });
      if (!empty($matches)) {
        sort($matches);
        return reset($matches);
      }
    }
    return $local_language;
  }

}
