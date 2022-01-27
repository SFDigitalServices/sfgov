<?php

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function sfgovpl_theme_suggestions_form_alter(array &$suggestions, array $variables) {
  $languages = \Drupal::languageManager()->getLanguages();
  $langArr = [];
  foreach ($languages as $language) {
    $langArr[] = $language->getId();
  }
  $langRegexStr = '\/(' . implode('|', $langArr) . ')?(\/)?search';
  $formAction = $variables['element']['#action'];
  preg_match("/" . $langRegexStr . "/", $formAction, $matches);
  if (count($matches) > 0) {
    $suggestions[] = "form__search";
  }
}

/**
 * Implements hook_form_alter().
 */
function sfgovpl_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if ($form['#id'] === 'user-login-form') {
    $form['name']['#description'] = t('Email address');
    $form['pass']['#description'] = t('<a href="@password">I forgot my password</a>', ['@password' => '/user/sfgov-password']);
  }
  elseif ($form['#id'] === 'user-pass') {
    $form['name']['#title'] = t('Email address or username');
    $form['name']['#description'] = t('We will send instructions to your email address on file.');
    unset($form['mail']);
  }
}

/**
 * Implements template_preprocess_form_element().
 */
function sfgovpl_preprocess_form_element(&$variables) {
  if (isset($variables['element']['#form_id'])) {
    if ($variables['element']['#form_id'] === 'user_login_form') {
      if ($variables['name'] === 'name' || $variables['name'] === 'pass') {
        $variables['description_display'] = 'before';
      }
    }
    elseif ($variables['element']['#form_id'] === 'user_pass') {
      if ($variables['name'] === 'name') {
        $variables['description_display'] = 'before';
      }
    }
  }
}
