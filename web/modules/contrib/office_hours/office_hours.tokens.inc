<?php

/**
 * @file
 * Provides tokens for the office_hours module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemList;

/**
 * Implements hook_tokens().
 */
function office_hours_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $replacements = [];

  if (empty($data['field_property'])) {
    return $replacements;
  }

  foreach ($tokens as $token => $original) {
    $list = $data[$data['field_name']];
    if (!$list instanceof OfficeHoursItemList) {
      continue;
    }

    $parts = explode(':', $token);
    $field = $list->get($parts[0]);

    $day = $field->get($parts[1])->getValue();
    switch ($parts[1]) {
      case 'day':
        $daynames = OfficeHoursDateHelper::weekDaysByFormat('long');
        $replacements[$original] = $daynames[$day];
        break;

      case 'starthours':
      case 'endhours':
        $replacements[$original] = OfficeHoursDateHelper::format($day, "H:i:s", FALSE);
        break;
    }
  }
  return $replacements;
}
