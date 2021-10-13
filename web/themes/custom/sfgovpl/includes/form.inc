<?php

/**
 * @param array $suggestions
 * @param array $variables
 */
function sfgovpl_theme_suggestions_form_alter(array &$suggestions, array $variables) {
  $languages = \Drupal::languageManager()->getLanguages();
  $langArr = [];
  foreach($languages as $language) {
    $langArr[] = $language->getId();
  }
  $langRegexStr = '\/(' . implode('|', $langArr) . ')?(\/)?search';
  $formAction = $variables['element']['#action'];
  preg_match("/" . $langRegexStr . "/", $formAction, $matches);
  if (count($matches) > 0) {
    $suggestions []= "form__search";
  }
}
