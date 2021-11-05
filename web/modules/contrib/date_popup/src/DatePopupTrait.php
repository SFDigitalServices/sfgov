<?php

namespace Drupal\date_popup;

/**
 * Shared code between the Date and Datetime plugins.
 */
trait DatePopupTrait {

  /**
   * Apply the HTML5 date popup to the views filter form.
   *
   * @param array $form
   *   The form to apply it to.
   */
  protected function applyDatePopupToForm(array &$form) {
    if (!empty($this->options['expose']['identifier'])) {
      $identifier = $this->options['expose']['identifier'];
      // Identify wrapper.
      $wrapper_key = $identifier . '_wrapper';
      if (isset($form[$wrapper_key])) {
        $element = &$form[$wrapper_key][$identifier];
      }
      else {
        $element = &$form[$identifier];
      }
      // Detect filters that are using min/max.
      if (isset($element['min'])) {
        $element['min']['#type'] = 'date';
        $element['max']['#type'] = 'date';
        if (isset($element['value'])) {
          $element['value']['#type'] = 'date';
        }
      }
      else {
        $element['#type'] = 'date';
      }
    }
  }

}
