<?php

namespace Drupal\office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Base class for the 'office_hours_*' widgets.
 */
abstract class OfficeHoursWidgetBase extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // N.B. The $values are already reformatted in the subWidgets.

    foreach ($values as $key => &$item) {
      // Numeric value is set in OfficeHoursDateList/Datetime::validateOfficeHours()
      $start = isset($item['starthours']['time']) ? $item['starthours']['time'] : $item['starthours'];
      $end   = isset($item['endhours']['time'])   ? $item['endhours']['time']   : $item['endhours'];

      if (empty($start) && empty($end) && empty($item['comment'])) {
        unset($values[$key]);
      }
      elseif (empty($start) && empty($end) && $item['comment'] != '') {
        // @todo: allow closed days with comment. However, this is prohibited.
        //        by the database: value '' is not allowed. The format is
        //        int(11). Would changing the format to 'string' help?
        unset($values[$key]);
      }
      else {
        // Avoid core's error "This value should be of the correct primitive type."
        // by casting the times to integer.
        // This is needed for e.g., 0000 and 0030.
        $item['starthours'] = (int) OfficeHoursDateHelper::format($start, 'Hi');
        $item['endhours'] = (int) OfficeHoursDateHelper::format($end, 'Hi');
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Get field settings, to make it accessible for each element in other functions.
    $settings = $this->getFieldSettings();

    $element['#field_settings'] = $settings;
    $element['value'] = [
      '#field_settings' => $settings,
      '#attached' => [
        'library' => [
          'office_hours/office_hours_widget',
        ],
      ],
    ];

    return $element;
  }

}
