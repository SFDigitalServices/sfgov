<?php

namespace Drupal\color_field\Plugin\Field\FieldFormatter;

use Drupal\color_field\Plugin\Field\FieldType\ColorFieldType;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\color_field\ColorHex;

/**
 * Plugin implementation of the color_field swatch formatter.
 *
 * @FieldFormatter(
 *   id = "color_field_formatter_swatch_options",
 *   module = "color_field",
 *   label = @Translation("Color swatch options"),
 *   field_types = {
 *     "color_field_type"
 *   }
 * )
 */
class ColorFieldFormatterSwatchOptions extends ColorFieldFormatterSwatch {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();

    $elements = [];

    $name = Html::getUniqueId("color-field");
    foreach ($items as $delta => $item) {
      $hex = $this->viewRawValue($item);
      $id = Html::getUniqueId("color-field-$hex");
      $elements[$delta] = [
        '#theme' => 'color_field_formatter_swatch_option',
        '#id' => $id,
        '#name' => $name,
        '#input_type' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple() ? 'checkbox' : 'radio',
        '#value' => $hex,
        '#shape' => $settings['shape'],
        '#height' => $settings['height'],
        '#width' => $settings['width'],
        '#color' => $this->viewValue($item),
      ];
    }

    return $elements;
  }

  /**
   * Return the raw field value.
   *
   * @param \Drupal\color_field\Plugin\Field\FieldType\ColorFieldType $item
   *   The color field item.
   *
   * @return string
   *   The color hex value.
   */
  protected function viewRawValue(ColorFieldType $item) {
    return (new ColorHex($item->color, $item->opacity))->toString(FALSE);
  }

}
