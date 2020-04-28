<?php

namespace Drupal\color_field\Plugin\Field\FieldFormatter;

use Drupal\color_field\Plugin\Field\FieldType\ColorFieldType;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\color_field\ColorHex;

/**
 * Plugin implementation of the color_field text formatter.
 *
 * @FieldFormatter(
 *   id = "color_field_formatter_text",
 *   module = "color_field",
 *   label = @Translation("Color text"),
 *   field_types = {
 *     "color_field_type"
 *   }
 * )
 */
class ColorFieldFormatterText extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format' => 'hex',
      'opacity' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $opacity = $this->getFieldSetting('opacity');

    $elements = [];

    $elements['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => $this->getColorFormat(),
      '#default_value' => $this->getSetting('format'),
    ];

    if ($opacity) {
      $elements['opacity'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display opacity'),
        '#default_value' => $this->getSetting('opacity'),
      ];
    }

    return $elements;
  }

  /**
   * This function is used to get the color format.
   *
   * @param string $format
   *   Format is of string type.
   *
   * @return array|string
   *   Returns array or string.
   */
  protected function getColorFormat($format = NULL) {
    $formats = [];
    $formats['hex'] = $this->t('Hex triplet');
    $formats['rgb'] = $this->t('RGB Decimal');

    if ($format) {
      return $formats[$format];
    }
    return $formats;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $opacity = $this->getFieldSetting('opacity');
    $settings = $this->getSettings();

    $summary = [];

    $summary[] = $this->t('@format', [
      '@format' => $this->getColorFormat($settings['format']),
    ]);

    if ($opacity && $settings['opacity']) {
      $summary[] = $this->t('Display with opacity.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(ColorFieldType $item) {
    $opacity = $this->getFieldSetting('opacity');
    $settings = $this->getSettings();

    $color_hex = new ColorHex($item->color, $item->opacity);

    switch ($settings['format']) {
      case 'hex':
        if ($opacity && $settings['opacity']) {
          $output = $color_hex->toString(TRUE);
        }
        else {
          $output = $color_hex->toString(FALSE);
        }
        break;

      case 'rgb':
        if ($opacity && $settings['opacity']) {
          $output = $color_hex->toRgb()->toString(TRUE);
        }
        else {
          $output = $color_hex->toRgb()->toString(FALSE);
        }
        break;
    }

    return $output;
  }

}
