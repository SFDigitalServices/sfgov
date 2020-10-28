<?php

namespace Drupal\sfgov_locations\Plugin\Field\FieldFormatter;

use Drupal\address\Plugin\Field\FieldFormatter\AddressPlainFormatter as AddressPlainFormatterBase;
use Drupal\sfgov_locations\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Locale;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\address\AddressInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'address_plain' formatter.,
 * )
 */
class AddressPlainFormatter extends AddressPlainFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function viewElement(AddressInterface $address, $langcode) {
    $element = parent::viewElement($address, $langcode);
    $country_code = $address->getCountryCode();
    $address_format = $this->addressFormatRepository->get($country_code);
    $values = $this->getValues($address, $address_format);
    $element['#addressee'] = $values['addressee'];
    $element['#location_name'] = $values['location_name'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getValues(AddressInterface $address, AddressFormat $address_format) {
    $values = [];
    foreach (AddressField::getAll() as $field) {
      $getter = 'get' . ucfirst($field);
      $values[$field] = $address->$getter();
    }

    $original_values = [];
    $subdivision_fields = $address_format->getUsedSubdivisionFields();
    $parents = [];
    foreach ($subdivision_fields as $index => $field) {
      $value = $values[$field];
      // The template needs access to both the subdivision code and name.
      $values[$field] = [
        'code' => $value,
        'name' => '',
      ];

      if (empty($value)) {
        // This level is empty, so there can be no sublevels.
        break;
      }
      $parents[] = $index ? $original_values[$subdivision_fields[$index - 1]] : $address->getCountryCode();
      $subdivision = $this->subdivisionRepository->get($value, $parents);
      if (!$subdivision) {
        break;
      }

      // Remember the original value so that it can be used for $parents.
      $original_values[$field] = $value;
      // Replace the value with the expected code.
      if (Locale::matchCandidates($address->getLocale(), $subdivision->getLocale())) {
        $values[$field] = [
          'code' => $subdivision->getLocalCode(),
          'name' => $subdivision->getLocalName(),
        ];
      }
      else {
        $values[$field] = [
          'code' => $subdivision->getCode(),
          'name' => $subdivision->getName(),
        ];
      }

      if (!$subdivision->hasChildren()) {
        // The current subdivision has no children, stop.
        break;
      }
    }

    return $values;
  }

}
