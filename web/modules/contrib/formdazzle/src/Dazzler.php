<?php

namespace Drupal\formdazzle;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderCallbackInterface;
use Drupal\Core\Render\Markup;

if (!function_exists('array_key_last')) {

  /**
   * PHP 7.2 and earlier backport of array_key_last().
   *
   * @param array $array
   *   The array in which to find the last element.
   *
   * @return mixed|null
   *   The index of the last element in the array.
   */
  function array_key_last(array $array) {
    if (!is_array($array) || empty($array)) {
      return NULL;
    }

    return array_keys($array)[count($array) - 1];
  }

}

/**
 * A class providing methods to modify Drupal form elements.
 *
 * @package Drupal\formdazzle\Dazzler
 */
class Dazzler implements RenderCallbackInterface {

  /**
   * Alters forms to add a late-running pre-render function.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param string $form_id
   *   The form id.
   */
  public static function formAlter(array &$form, $form_id) {
    // Instead of altering the form now, we wait until all hook_form_alter
    // functions are completed and make our changes during the #pre_render
    // phase of Drupal\Core\Render\Renderer::render().
    $form['#formdazzle'] = ['form_id' => $form_id];

    // Add our pre-render function to the end of the list.
    if (!isset($form['#pre_render'])) {
      $form['#pre_render'] = [];
    }
    $form['#pre_render'][] = [self::class, 'preRenderForm'];
  }

  /**
   * Adds template suggestions to forms.
   *
   * Instead of using hook_form_alter or hook_theme_suggestions_alter, we delay
   * adding suggestions until the form's pre_render phase.
   *
   * @param array $form
   *   A render array of the form.
   *
   * @return array
   *   The modified form.
   */
  public static function preRenderForm(array $form) {
    // We always set these properties in formAlter(). If this is missing, an
    // earlier call to preRenderForm() has already consumed the #formdazzle
    // data. So we don't need to run this function.
    if (isset($form['#formdazzle']['form_id'])) {
      $form_id = $form['#formdazzle']['form_id'];
      $form_id_suggestion = self::getFormIdSuggestion($form, $form_id);
      self::traverse($form, $form_id, $form_id_suggestion);
      // We unset the #formdazzle data to prevent repeated preRenderForm() calls
      // from altering the form again.
      unset($form['#formdazzle']);

      // When #theme is set, Drupal ignores #markup, UNLESS none of the #theme
      // suggestions are implemented. Which means we can safely set #markup to
      // print out Twig debugging comments about the not-implemented #theme
      // suggestions.
      /** @var \Twig_Environment $twig_service */
      $twig_service = \Drupal::service('twig');
      if ($twig_service->isDebug() && isset($form['#theme'])) {
        // Expand the list of theme suggestions.
        $suggestions = $form['#theme'];
        if (is_string($suggestions)) {
          $suggestions = [$suggestions];
        }
        $hook = $suggestions[array_key_last($suggestions)];
        while ($pos = strrpos($hook, '__')) {
          $hook = substr($hook, 0, $pos);
          $suggestions[] = $hook;
        }

        // Add an HTML comment that mimics the Twig debugging comments added by
        // twig.engine. @see twig_render_template()
        foreach ($suggestions as &$suggestion) {
          $suggestion = Html::escape(strtr($suggestion, '_', '-') . '.html.twig');
        }
        $form['#markup'] = Markup::create("\n\n<!-- THEME DEBUG -->"
          . "\n<!-- THEME HOOK: No templates found. -->"
          . "\n<!-- FILE NAME SUGGESTIONS:\n   * "
          . implode("\n   * ", $suggestions)
          . "\n-->"
        );
      }
    }

    // #pre_render functions return the elements they are pre-rendering.
    return $form;
  }

  /**
   * Generate a more useful form ID suggestion.
   *
   * @param array $form
   *   The form to examine.
   * @param string $form_id
   *   The ID of the form.
   *
   * @return string
   *   The generated $form_id_suggestion.
   */
  public static function getFormIdSuggestion(array &$form, $form_id) {
    $form_id_suggestion = $form_id;

    $last_suggestion = '';
    if (isset($form['#theme'])) {
      if (is_string($form['#theme'])) {
        $last_suggestion = $form['#theme'];
      }
      else {
        $last_suggestion = $form['#theme'][array_key_last($form['#theme'])];
      }
    }

    // Use a simplified webform form ID.
    if ($last_suggestion === 'webform_submission_form' && isset($form['#webform_id'])) {
      $form_id_suggestion = 'webform_' . $form['#webform_id'];
    }

    // For the views exposed form, add the View name and display ID.
    elseif ($form_id === 'views_exposed_form') {
      // The first theme suggestion includes both the View name and display ID.
      // @see Drupal\views\ViewExecutable::buildThemeFunctions().
      $form_id_suggestion = str_replace('views_exposed_form__', 'views__', $form['#theme'][0]);
    }

    return $form_id_suggestion;
  }

  /**
   * A recursive helper function to traverse all form element children.
   *
   * @param array $element
   *   The form or form element to recurse.
   * @param string $form_id
   *   The ID of the form the element belongs to.
   * @param string $form_id_suggestion
   *   A suggestion to use based on the form ID.
   */
  public static function traverse(array &$element, $form_id, $form_id_suggestion) {
    // Add the default info for the #type of form element.
    self::addDefaultThemeProperties($element);

    // Add template suggestions to form element.
    self::addSuggestions($element, $form_id, $form_id_suggestion);

    // Traverse all the element's children.
    foreach (Element::children($element) as $element_name) {
      self::traverse($element[$element_name], $form_id, $form_id_suggestion);
    }
  }

  /**
   * Adds default theme and theme_wrappers info to form elements.
   *
   * The Drupal\Core\Render\Renderer::doRender() function adds the default
   * properties for each element type. Since these sometimes include #theme and
   * #theme_wrappers, we want to use those now, so we add those two properties
   * now and let render() handle the other default properties later.
   *
   * @param array $element
   *   The form or form element.
   */
  public static function addDefaultThemeProperties(array &$element) {
    if (isset($element['#type'])) {
      $default_theme_properties = array_intersect_key(
        \Drupal::service('element_info')->getInfo($element['#type']),
        ['#theme' => TRUE, '#theme_wrappers' => TRUE]
      );
      $element += $default_theme_properties;
    }
  }

  /**
   * Adds theme suggestions to form elements.
   *
   * @param array $element
   *   The form or form element to give a template suggestion to.
   * @param string $form_id
   *   The ID of the form the element belongs to.
   * @param string $form_id_suggestion
   *   A suggestion to use based on the form ID.
   */
  public static function addSuggestions(array &$element, $form_id, $form_id_suggestion) {
    $needs_theme_suggestion = isset($element['#theme']);
    $needs_theme_wrapper_suggestion = isset($element['#theme_wrappers']);

    // #theme can be a string or an array. To ease processing, we ensure it is
    // an array and flatten single-item arrays before we leave this function.
    $flatten_theme_array = FALSE;
    if ($needs_theme_suggestion && !is_array($element['#theme'])) {
      $element['#theme'] = [$element['#theme']];
      $flatten_theme_array = TRUE;
    }

    $last_suggestion_key = $needs_theme_suggestion
      ? array_key_last($element['#theme'])
      : NULL;
    $last_suggestion = !is_null($last_suggestion_key) ? $element['#theme'][$last_suggestion_key] : '';

    // Find the form element name.
    $name = '';
    if (isset($element['#name'])) {
      $name = $element['#name'];
    }
    elseif (isset($element['#webform_key'])) {
      $name = $element['#webform_key'];
    }
    elseif (isset($element['#parents']) && count($element['#parents'])) {
      // Based on Drupal\Core\Form\FormBuilder::handleInputElement().
      if (isset($element['#type']) && $element['#type'] === 'file') {
        $name = 'files_' . $element['#parents'][0];
      }
      else {
        $name = implode('_', $element['#parents']);
      }
    }

    $name_suggestion = '';
    if ($name) {
      // Ensure the name is a proper suggestion string.
      // Based on regex in Drupal\Component\Utility\Html::getId().
      $name = strtolower(strtr($name, [
        ' ' => '_',
        '.' => '_',
        '-' => '_',
        '/' => '_',
        '[' => '_',
        ']' => '',
      ]));
      $name = preg_replace('/[^a-z0-9_]/', '', $name);
      $name_suggestion = '__' . $name;
    }

    // Most Drupal\Core\Render\Element's have generic suggestions, but some
    // don't have them when they should.
    $type = isset($element['#type']) ? $element['#type'] : '';
    $type_suggestion = '';
    switch ($type) {
      case 'actions':
      case 'more_link':
      case 'password_confirm':
      case 'system_compact_link':
        $type_suggestion = '__' . $type;
        break;
    }
    // Don't add name suggestions that are redundant with type suggestions.
    if ($type_suggestion && $type_suggestion === $name_suggestion) {
      $name_suggestion = '';
    }

    // Use the same theme suggestions for each theme hook.
    $suggestion_suffix = $type_suggestion . '__' . $form_id_suggestion . $name_suggestion;

    // We want to ensure that all form elements have a generic theme suggestion.
    // But form_element doesn't create a label element until after form_alter
    // hooks have run, so we have to add suggestion data so that later hooks can
    // add suggestions to the form.
    $is_form_element = $needs_theme_wrapper_suggestion
      && in_array('form_element', $element['#theme_wrappers']);
    if ($is_form_element) {
      $element['#formdazzle'] = [
        'suggestion_suffix' => $suggestion_suffix,
        'form_id' => $form_id,
        'form_id_suggestion' => $form_id_suggestion,
        'form_element_name' => $name,
      ];
    }

    // Add theme suggestions to #theme.
    // In Drupal\Core\Theme\ThemeManager::render(), if an array of hook
    // candidates is passed, use the first exact match. If there are no exact
    // matches, the last hook candidate is checked for more generic fallbacks by
    // stripping off successive __[suggestion] parts from the end of the string
    // until a match is made.
    if ($needs_theme_suggestion) {
      if ($type === 'form') {
        // This is technically a duplicate of an existing suggestion, but Twig
        // debugging doesn't show the suggestions unless we add this duplicate.
        // @see https://www.drupal.org/project/drupal/issues/2118743
        if ($last_suggestion === 'views_exposed_form') {
          $element['#theme'][$last_suggestion_key] .= str_replace('__views__', '__', $suggestion_suffix);
        }
      }
      // If we aren't examining the root form, add #theme suggestions.
      else {
        $element['#theme'][$last_suggestion_key] .= $suggestion_suffix;
      }

      // If we converted a #theme string into an array, convert it back.
      if ($flatten_theme_array && count($element['#theme']) === 1) {
        $element['#theme'] = $element['#theme'][0];
      }
    }

    // Add theme suggestions to #theme_wrappers.
    // In Drupal\Core\Theme\ThemeManager::render(), #theme wrappers is an array
    // of theme hooks. The keys of the array are either numeric keys (with the
    // theme hook as the value) or strings containing the theme hook (and the
    // value is an array of data not important to FormDazzle.) e.g.
    // [ 0 => 'theme_hook', 'theme_hook' => ['some' => 'data']].
    if ($needs_theme_wrapper_suggestion) {
      $add_suggestions_to_keys = FALSE;
      foreach ($element['#theme_wrappers'] as $key => $value) {
        if (is_string($key)) {
          $add_suggestions_to_keys = TRUE;
        }
        elseif (strpos($value, $suggestion_suffix) === FALSE) {
          $element['#theme_wrappers'][$key] = $value . $suggestion_suffix;
        }
      }

      // In order to add suggestions to array keys, we have to rebuild the array
      // to ensure that the order of the array isn't disturbed.
      if ($add_suggestions_to_keys) {
        $theme_wrappers = $element['#theme_wrappers'];
        $element['#theme_wrappers'] = [];
        foreach ($theme_wrappers as $key => $value) {
          if (is_string($key)) {
            $element['#theme_wrappers'][$key . $suggestion_suffix] = $value;
          }
          else {
            $element['#theme_wrappers'][] = $value;
          }
        }
      }
    }
  }

  /**
   * Adds theme suggestions to a form_element's label.
   *
   * @param array $variables
   *   The variables that will be passed to the form_element Twig template.
   */
  public static function preprocessFormElement(array &$variables) {
    if (isset($variables['element']['#formdazzle'])) {
      $suggestion_suffix = $variables['element']['#formdazzle']['suggestion_suffix'];

      if (is_string($variables['label']['#theme'])) {
        $variables['label']['#theme'] .= $suggestion_suffix;
      }
      elseif (is_array($variables['label']['#theme'])) {
        $last_suggestion = array_key_last($variables['label']['#theme']);
        $variables['label']['#theme'][$last_suggestion] .= $suggestion_suffix;
      }
    }
  }

  /**
   * In the list of module form_alter hooks, move formdazzle to be last.
   *
   * @param string|FALSE[] $implementations
   *   An array keyed by the module's name.
   * @param string $hook
   *   The name of the module hook being implemented.
   */
  public static function moduleImplementsAlter(&$implementations, $hook) {
    if ($hook === 'form_alter') {
      $group = $implementations['formdazzle'];
      unset($implementations['formdazzle']);
      $implementations['formdazzle'] = $group;
    }
  }

}
