<?php

namespace Drupal\sfgov_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sfgov_media\Plugin\media\Source\PowerBi;

/**
 * Plugin implementation of the 'power_bi' formatter.
 *
 * @FieldFormatter(
 *   id = "power_bi",
 *   label = @Translation("Power BI Embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class PowerBiEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '460',
      'height' => '355',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
      '#required' => TRUE,
    ];

    $elements['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#min' => 1,
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Width: @width', [
        '@width' => $this->getSetting('width'),
      ]),
      $this->t('Height: @height', [
        '@height' => $this->getSetting('height'),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $items->getEntity();

    $element = [];
    if (($source = $media->getSource()) && $source instanceof PowerBi) {
      /** @var \Drupal\media\MediaTypeInterface $item */
      foreach ($items as $delta => $item) {
        if ($url = $source->getMetadata($media, 'embed_url')) {
          $element[$delta] = [
            '#theme' => 'media_power_bi',
            '#url' => $url,
            '#width' => $this->getSetting('width'),
            '#height' => $this->getSetting('height'),
          ];
        }
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'media';
  }

}
