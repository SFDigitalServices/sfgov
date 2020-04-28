<?php

namespace Drupal\telephone_validation\Render\Element;

use Drupal\Core\Render\Element\Tel;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides element validation.
 *
 * Usage example:
 * @code
 * $form['phone'] = array(
 *   '#type' => 'tel',
 *   '#title' => t('Phone'),
 *    // Add #element_validate to your form element.
 *   '#element_validate' => [['Drupal\telephone_validation\Render\Element\TelephoneValidation', 'validateTel']],
 *    // Customize validation settings. If not global settings will be in use.
 *   '#element_validate_settings' => [
 *     // By default input format should be consistent with E164 standard.
 *     'valid_format' => PhoneNumberFormat::E164,
 *     // By default all countries are valid.
 *     'valid_countries' => [],
 *   ],
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Tel
 *
 * @FormElement("tel")
 */
class TelephoneValidation extends Tel {

  /**
   * Form element validation handler.
   *
   * Note that #maxlength and #required is validated by _form_validate()
   * already.
   */
  public static function validateTel(&$element, FormStateInterface $form_state, &$complete_form) {

    // Get validation service.
    $service = \Drupal::service('telephone_validation.validator');

    // Normalize value.
    $value = $element['#value'];

    // Check if value is valid (if not empty).
    if ($value !== '' && !$service->isValid($value, $element['#element_validate_settings']['format'], $element['#element_validate_settings']['country'])) {
      $form_state->setError($element, t('The phone number %phone is not valid.', ['%phone' => $value]));
    }
  }

}
