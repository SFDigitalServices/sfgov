<?php

namespace Drupal\sfgov_locations;

use Drupal\address\FieldHelper as FieldHelperBase;
use Drupal\sfgov_locations\AddressField;

/**
 * Provides property names and autocomplete attributes for AddressField values.
 */
class FieldHelper extends FieldHelperBase {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyName($field) {
    $mapping = [];
    if ($mapping = parent::getPropertyName($field)) {
      return $mapping;
    }
    else {
      $mapping[AddressField::ADDRESSEE] = 'addressee';
      $mapping[AddressField::LOCATION_NAME] = 'location_name';
      return isset($mapping[$field]) ? $mapping[$field] : NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getAutocompleteAttribute($field) {
    $mapping = [];
    if ($mapping = parent::getAutocompleteAttribute($field)) {
      return $mapping;
    }
    else {
      $mapping[AddressField::ADDRESSEE] = 'addressee';
      $mapping[AddressField::LOCATION_NAME] = 'location_name';
      return isset($mapping[$field]) ? $mapping[$field] : NULL;
    }
  }

}
