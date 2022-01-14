<?php

namespace Drupal\sfgov_dates\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'SfgovSmartdate' formatter.
 *
 * @FieldFormatter(
 *   id = "sfgov_dates_sfgovSmartdate",
 *   label = @Translation("SfgovSmartdate"),
 *   field_types = {
 *     "smartdate",
 *     "daterange"
 *   }
 * )
 */
class SfgovSmartdateFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $item->value,
      ];
    }

    return $element;
  }

}
