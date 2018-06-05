<?php

/**
 * @param array $suggestions
 * @param array $variables
 */
function sfgovpl_theme_suggestions_form_alter(array &$suggestions, array $variables) {
  if ($variables['element']['#action'] === '/search') {
    $suggestions []= "form__search";
  }
}
