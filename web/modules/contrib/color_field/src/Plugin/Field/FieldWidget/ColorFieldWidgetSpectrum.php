<?php

namespace Drupal\color_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the color_field spectrum widget.
 *
 * @FieldWidget(
 *   id = "color_field_widget_spectrum",
 *   module = "color_field",
 *   label = @Translation("Color spectrum"),
 *   field_types = {
 *     "color_field_type"
 *   }
 * )
 */
class ColorFieldWidgetSpectrum extends ColorFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_input' => FALSE,
      'show_palette' => FALSE,
      'palette' => '',
      'show_palette_only' => FALSE,
      'show_buttons' => FALSE,
      'allow_empty' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['show_input'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Input'),
      '#default_value' => $this->getSetting('show_input'),
      '#description' => $this->t('Allow free form typing.'),
    ];
    $element['show_palette'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Palette'),
      '#default_value' => $this->getSetting('show_palette'),
      '#description' => $this->t('Show or hide Palette in Spectrum Widget'),
    ];
    $element['palette'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Color Palette'),
      '#default_value' => $this->getSetting('palette'),
      '#description' => $this->t('Selectable color palette to accompany the Spectrum Widget. Separate values with a comma, and group them with square brackets - ensure one group per line. Ex: <br> ["#fff","#aaa","#f00","#00f"],<br>["#414141","#242424","#0a8db9"]'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][show_palette]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['show_palette_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Palette Only'),
      '#default_value' => $this->getSetting('show_palette_only'),
      '#description' => $this->t('Only show the palette in Spectrum Widget and nothing else'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][show_palette]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['show_buttons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Buttons'),
      '#default_value' => $this->getSetting('show_buttons'),
      '#description' => $this->t('Add Cancel/Confirm Button.'),
    ];
    $element['allow_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Empty'),
      '#default_value' => $this->getSetting('allow_empty'),
      '#description' => $this->t('Allow empty value.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#attached']['library'][] = 'color_field/color-field-widget-spectrum';

    // Set Drupal settings.
    $settings = $this->getSettings();

    // Compare with default settings make sure they are the same datatype.
    $defaults = self::defaultSettings();
    foreach ($settings as $key => $value) {
      if (is_bool($defaults[$key])) {
        $settings[$key] = boolval($value);
      }
    }

    // Parsing Palette data so that it works with spectrum color picker.
    // This will create a multidimensional array of hex values.
    // Do some cleanup to reduce risk of malformed data.
    if (!empty($settings['palette'])) {
      // Remove any whitespace.
      $settings['palette'] = str_replace([' ', "\n", '"', "'"], '', $settings['palette']);

      // Parse each row first and reset the palette.
      $rows = explode("\r", $settings['palette']);
      $settings['palette'] = [];

      foreach ($rows as $row) {
        // Next explode each row into an array of values.
        $settings['palette'][] = explode(',', trim($row, " \t\n\r\0\x0B,]["));
      }
    }

    $settings['show_alpha'] = (bool) $this->getFieldSetting('opacity');
    $element['#attributes']['id'] = $element['#uid'];
    $element['#attributes']['class'][] = 'js-color-field-widget-spectrum';
    $element['#attached']['drupalSettings']['color_field']['color_field_widget_spectrum'][$element['#uid']] = $settings;
    $element['color']['#attributes']['class'][] = 'js-color-field-widget-spectrum__color';
    $element['opacity']['#attributes']['class'][] = 'js-color-field-widget-spectrum__opacity';

    return $element;
  }

}
