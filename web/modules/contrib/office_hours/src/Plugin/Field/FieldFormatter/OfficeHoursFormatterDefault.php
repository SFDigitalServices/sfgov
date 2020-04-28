<?php

namespace Drupal\office_hours\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface;

/**
 * Plugin implementation of the formatter.
 *
 * @FieldFormatter(
 *   id = "office_hours",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "office_hours",
 *   }
 * )
 */
class OfficeHoursFormatterDefault extends OfficeHoursFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = '(When using multiple slots per day, better use the table formatter.)';
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $settings = $this->getSettings();
    // If no data is filled for this entity, do not show the formatter.
    // N.B. 'Show current day' may return nothing in getRows(), while other days are filled.
    /* @var $items OfficeHoursItemListInterface */
    if (!$items->getValue()) {
      return $elements;
    }
    $office_hours = $items->getRows($settings, $this->getFieldSettings());

    if ($office_hours) {
      $elements[] = [
        '#theme' => 'office_hours',
        '#office_hours' => $office_hours,
        '#item_separator' => $settings['separator']['days'],
        '#slot_separator' => $settings['separator']['more_hours'],
        'class' => ['office-hours',],
        //'#empty' => t('This location has no opening hours.'),
        '#attached' => [
          'library' => [
            'office_hours/office_hours_formatter',
          ],
        ],
        '#cache' => [
          'max-age' => $this->getStatusTimeLeft($items, $langcode),
        ],
      ];
    }

    $elements = $this->addStatusFormatter($items, $langcode, $elements);

    return $elements;
  }

}
