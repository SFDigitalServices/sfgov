<?php

namespace Drupal\sfgov_locations;

use CommerceGuys\Addressing\AddressFormat\AddressFormat as AddressFormatBase;
use CommerceGuys\Addressing\AddressFormat\AdministrativeAreaType;
use CommerceGuys\Addressing\AddressFormat\DependentLocalityType;
use CommerceGuys\Addressing\AddressFormat\LocalityType;
use CommerceGuys\Addressing\AddressFormat\PostalCodeType;
use Drupal\sfgov_locations\AddressField;

/**
 * Provides metadata for storing and presenting a country's addresses.
 */
class AddressFormat extends AddressFormatBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $definition) {
    // Validate the presence of required properties.
    foreach (['country_code', 'format'] as $requiredProperty) {
      if (empty($definition[$requiredProperty])) {
        throw new \InvalidArgumentException(sprintf('Missing required property %s.', $requiredProperty));
      }
    }
    // Add defaults for properties that are allowed to be empty.
    $definition += [
      'locale' => null,
      'local_format' => null,
      'required_fields' => [],
      'uppercase_fields' => [],
      'postal_code_pattern' => null,
      'postal_code_prefix' => null,
      'subdivision_depth' => 0,
    ];
    AddressField::assertAllExist($definition['required_fields']);
    AddressField::assertAllExist($definition['uppercase_fields']);
    $this->countryCode = $definition['country_code'];
    $this->locale = $definition['locale'];
    $this->format = $definition['format'];
    $this->localFormat = $definition['local_format'];
    $this->requiredFields = $definition['required_fields'];
    $this->uppercaseFields = $definition['uppercase_fields'];
    $this->subdivisionDepth = $definition['subdivision_depth'];

    $usedFields = $this->getUsedFields();
    if (in_array(AddressField::ADMINISTRATIVE_AREA, $usedFields)) {
      if (isset($definition['administrative_area_type'])) {
        AdministrativeAreaType::assertExists($definition['administrative_area_type']);
        $this->administrativeAreaType = $definition['administrative_area_type'];
      }
    }
    if (in_array(AddressField::LOCALITY, $usedFields)) {
      if (isset($definition['locality_type'])) {
        LocalityType::assertExists($definition['locality_type']);
        $this->localityType = $definition['locality_type'];
      }
    }
    if (in_array(AddressField::DEPENDENT_LOCALITY, $usedFields)) {
      if (isset($definition['dependent_locality_type'])) {
        DependentLocalityType::assertExists($definition['dependent_locality_type']);
        $this->dependentLocalityType = $definition['dependent_locality_type'];
      }
    }
    if (in_array(AddressField::POSTAL_CODE, $usedFields)) {
      if (isset($definition['postal_code_type'])) {
        PostalCodeType::assertExists($definition['postal_code_type']);
        $this->postalCodeType = $definition['postal_code_type'];
      }
      $this->postalCodePattern = $definition['postal_code_pattern'];
      $this->postalCodePrefix = $definition['postal_code_prefix'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedFields() {
    if (empty($this->usedFields)) {
      $this->usedFields = [];
      foreach (AddressField::getAll() as $field) {
        if (strpos($this->format, '%' . $field) !== false) {
          $this->usedFields[] = $field;
        }
      }
    }

    return $this->usedFields;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedSubdivisionFields() {
    $fields = [
      AddressField::ADMINISTRATIVE_AREA,
      AddressField::LOCALITY,
      AddressField::DEPENDENT_LOCALITY,
    ];
    // Remove fields not used by the format, and reset the keys.
    $fields = array_intersect($fields, $this->getUsedFields());
    $fields = array_values($fields);

    return $fields;
  }

}
