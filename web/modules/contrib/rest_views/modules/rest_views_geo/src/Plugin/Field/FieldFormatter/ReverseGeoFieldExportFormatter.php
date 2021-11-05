<?php

namespace Drupal\rest_views_geo\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\rest_views\SerializedData;

/**
 * Export a Geo field.
 *
 * @FieldFormatter(
 *   id = "reverse_geo_formatter_export",
 *   label = @Translation("Export"),
 *   field_types = {
 *     "reverse_geo"
 *   }
 * )
 */
class ReverseGeoFieldExportFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $data = [
        'address1' => $item->address1,
        'address2' => $item->address2,
        'city' => $item->city,
        'postcode' => $item->postcode,
        'latitude' => $item->lat,
        'longitude' => $item->lng,
      ];
      $elements[$delta] = [
        '#type' => 'data',
        '#data' => SerializedData::create($data),
      ];
    }

    return $elements;
  }

}
