<?php

/**
 * @file
 * Sample field formatter plugin for the REST Views module.
 */

namespace Drupal\my_module\Plugins\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\rest_views\SerializedData;

/**
 * A sample REST-aware field formatter for a custom field.
 *
 * @FieldFormatter(
 *   id = "my_field_export",
 *   label = @Translation("Export my field"),
 *   field_types = {
 *     "my_field",
 *   }
 * )
 */
class MyFieldExportFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $data = '[A data structure based on $item.]';
      $elements[$delta] = [
        '#type' => 'data',
        '#data' => SerializedData::create($data),
      ];
    }

    return $elements;
  }

}
