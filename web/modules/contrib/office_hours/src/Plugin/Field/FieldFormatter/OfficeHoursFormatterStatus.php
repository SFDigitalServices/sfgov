<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

/**
 * Plugin implementation of the formatter.
 * It is not visible on Field's 'Manage display' page.
 *
 * @FieldFormatter(
 *   id = "office_hours_status",
 *   label = @Translation("Office hours status"),
 *   field_types = {
 *     "office_hours_status",
 *   }
 * )
 */
class OfficeHoursFormatterStatus extends OfficeHoursFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $settings = $this->getSettings();

    // Alter the default settings, to calculate the cache correctly.
    // The status formatter has no UI for this setting.
    $this->setSetting('show_closed', 'next');

    /* @var $items OfficeHoursItemListInterface */
    $elements += [
      '#theme' => 'office_hours_status',
      '#is_open' => $items->isOpen(),
      '#open_text' => (string) $this->t($settings['current_status']['open_text']),
      '#closed_text' => (string) $this->t($settings['current_status']['closed_text']),
      '#position' => $this->settings['current_status']['position'],
      '#cache' => [
        'max-age' => $this->getStatusTimeLeft($items, $langcode),
      ],
    ];

    return $elements;
  }

}
