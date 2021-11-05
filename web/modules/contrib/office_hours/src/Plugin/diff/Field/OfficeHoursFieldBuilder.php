<?php

namespace Drupal\office_hours\Plugin\diff\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\diff\FieldDiffBuilderBase;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Plugin to diff office hours fields.
 *
 * @FieldDiffBuilder(
 *   id = "office_hours_diff_builder",
 *   label = @Translation("Office Hours Field Diff"),
 *   field_types = {
 *     "office_hours"
 *   },
 * )
 */
class OfficeHoursFieldBuilder extends FieldDiffBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items) {
    $result = [];
    $day_names = OfficeHoursDateHelper::weekDays(TRUE);

    /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
    foreach ($field_items as $field_key => $field_item) {
      if (!$field_item->isEmpty()) {
        $values = $field_item->getValue();
        $result[$field_key][] =
          $day_names[$values['day']]
          . ': ' . OfficeHoursDateHelper::format($values['starthours'], 'H:i')
          . ' - ' . OfficeHoursDateHelper::format($values['endhours'], 'H:i')
          . ' ' . $values['comment'];
      }
    }

    return $result;
  }

}
