<?php

namespace Drupal\sfgov_locations\Element;

use Drupal\address\Element\Address as AddressBase;
use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use CommerceGuys\Addressing\Locale;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\sfgov_locations\AddressFormatHelper;
use Drupal\sfgov_locations\AddressField;
use Drupal\sfgov_locations\FieldOverrides;
use Drupal\sfgov_locations\FieldHelper;
use Drupal\sfgov_locations\LabelHelper;

/**
 * Extends Address module main class.
 */
class Address extends AddressBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo();
  }

  /**
   * Ensures all keys are set on the provided value.
   *
   * @param array $value
   *   The value.
   *
   * @return array
   *   The modified value.
   */
  public static function applyDefaults(array $value) {
    $properties = [
      'given_name', 'additional_name', 'family_name', 'organization',
      'address_line1', 'address_line2', 'postal_code', 'sorting_code',
      'dependent_locality', 'locality', 'administrative_area',
      'country_code', 'langcode', 'addressee', 'location_name',
    ];
    foreach ($properties as $property) {
      if (!isset($value[$property])) {
        $value[$property] = NULL;
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Ensure both the default value and the input have all keys set.
    // Preselect the default country to ensure it's present in the value.
    $element['#default_value'] = (array) $element['#default_value'];
    $element['#default_value'] = self::applyDefaults($element['#default_value']);
    if (empty($element['#default_value']['country_code']) && $element['#required']) {
      $element['#default_value']['country_code'] = Country::getDefaultCountry($element['#available_countries']);
    }
    // Any input with a NULL or missing country_code is considered invalid.
    // Even if the element is optional and no country is selected, the
    // country_code would be an empty string, not NULL.
    if (is_array($input) && !isset($input['country_code'])) {
      $input = NULL;
    }
    if (is_array($input)) {
      $input = self::applyDefaults($input);
      if (empty($input['country_code']) && $element['#required']) {
        $input['country_code'] = $element['#default_value']['country_code'];
      }
    }

    return is_array($input) ? $input : $element['#default_value'];
  }

  /**
   * Processes the address form element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @throws \InvalidArgumentException
   *   Thrown when #used_fields is malformed.
   */
  public static function processAddress(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Convert #used_fields into #field_overrides.
    if (!empty($element['#used_fields']) && is_array($element['#used_fields'])) {
      $unused_fields = array_diff(AddressField::getAll(), $element['#used_fields']);
      $element['#field_overrides'] = [];
      foreach ($unused_fields as $field) {
        $element['#field_overrides'][$field] = FieldOverride::HIDDEN;
      }
      unset($element['#used_fields']);
    }
    // Validate and parse #field_overrides.
    if (!is_array($element['#field_overrides'])) {
      throw new \InvalidArgumentException('The #field_overrides property must be an array.');
    }
    $element['#parsed_field_overrides'] = new FieldOverrides($element['#field_overrides']);

    $id_prefix = implode('-', $element['#parents']);
    $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    // The #value has the new values on #ajax, the #default_value otherwise.
    $value = $element['#value'];

    $element = [
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapper_id,
    ] + $element;
    $element['langcode'] = [
      '#type' => 'hidden',
      '#value' => $element['#default_value']['langcode'],
    ];
    $element['country_code'] = [
      '#type' => 'address_country',
      '#title' => t('Country'),
      '#available_countries' => $element['#available_countries'],
      '#default_value' => $element['#default_value']['country_code'],
      '#required' => $element['#required'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
      '#weight' => -100,
    ];
    if (!empty($value['country_code'])) {
      $element = static::addressElements($element, $value);
    }

    return $element;
  }

  /**
   * Builds the format-specific address elements.
   *
   * @param array $element
   *   The existing form element array.
   * @param array $value
   *   The address value, in $property_name => $value format.
   *
   * @return array
   *   The modified form element array containing the format specific elements.
   */
  protected static function addressElements(array $element, array $value) {
    $size_attributes = [
      AddressField::ADMINISTRATIVE_AREA => 30,
      AddressField::LOCALITY => 30,
      AddressField::DEPENDENT_LOCALITY => 30,
      AddressField::POSTAL_CODE => 10,
      AddressField::SORTING_CODE => 10,
      AddressField::GIVEN_NAME => 25,
      AddressField::ADDITIONAL_NAME => 25,
      AddressField::FAMILY_NAME => 25,
    ];
    $field_overrides = $element['#parsed_field_overrides'];
    /** @var \CommerceGuys\Addressing\AddressFormat\AddressFormat $address_format */
    $address_format = \Drupal::service('sfgov_locations.address_format_repository')->get($value['country_code']);
    $required_fields = AddressFormatHelper::getRequiredFields($address_format, $field_overrides);
    $labels = LabelHelper::getFieldLabels($address_format);
    $locale = \Drupal::languageManager()->getConfigOverrideLanguage()->getId();
    if (Locale::matchCandidates($address_format->getLocale(), $locale)) {
      $format_string = $address_format->getLocalFormat();
    }
    else {
      $format_string = $address_format->getFormat();
    }
    $grouped_fields = AddressFormatHelper::getGroupedFields($format_string, $field_overrides);
    foreach ($grouped_fields as $line_index => $line_fields) {
      if (count($line_fields) > 1) {
        // Used by the #pre_render callback to group fields inline.
        $element['container' . $line_index] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['address-container-inline'],
          ],
        ];
      }

      foreach ($line_fields as $field_index => $field) {
        $property = FieldHelper::getPropertyName($field);
        $class = str_replace('_', '-', $property);

        $element[$property] = [
          '#type' => 'textfield',
          '#title' => $labels[$field],
          '#default_value' => isset($value[$property]) ? $value[$property] : '',
          '#required' => in_array($field, $required_fields),
          '#size' => isset($size_attributes[$field]) ? $size_attributes[$field] : 60,
          '#attributes' => [
            'class' => [$class],
            'autocomplete' => FieldHelper::getAutocompleteAttribute($field),
          ],
        ];
        if (count($line_fields) > 1) {
          $element[$property]['#group'] = $line_index;
        }
      }
    }
    // Hide the label for the second address line.
    if (isset($element['address_line2'])) {
      $element['address_line2']['#title_display'] = 'invisible';
    }

    // Add predefined options to the created subdivision elements.
    $element = static::processSubdivisionElements($element, $value, $address_format);
    return $element;
  }

}
