<?php

namespace Drupal\sfgov_locations;

use Drupal\address\LabelHelper as LabelHelperBase;
use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use Drupal\sfgov_locations\AddressField;

/**
 * Provides translated labels for the library enums.
 */
class LabelHelper extends LabelHelperBase {

  /**
   * {@inheritdoc}
   */
  public static function getGenericFieldLabels() {
    $labels = parent::getGenericFieldLabels();
    $labels[AddressField::ADDRESSEE] = t('Addressee', [], ['context' => 'Address label']);
    $labels[AddressField::LOCATION_NAME] = t('Location name', [], ['context' => 'Address label']);
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public static function getFieldLabels(AddressFormat $address_format) {
    $labels = parent::getFieldLabels($address_format);
    $labels[AddressField::ADDRESSEE] = t('Addressee', [], ['context' => 'Address label']);
    $labels[AddressField::LOCATION_NAME] = t('Location name', [], ['context' => 'Address label']);
    return $labels;
  }

}
