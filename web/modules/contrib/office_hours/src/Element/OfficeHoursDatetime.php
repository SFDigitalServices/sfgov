<?php

namespace Drupal\office_hours\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Provides a one-line text field form element.
 *
 * @FormElement("office_hours_datetime")
 */
class OfficeHoursDatetime extends Datetime {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $parent_info = parent::getInfo();

    $info = [
//      '#input' => TRUE,
//      '#tree' => TRUE,
      '#process' => [
        [$class, 'processOfficeHours'],
      ],
      '#element_validate' => [
        [$class, 'validateOfficeHours'],
      ],

      // @see Drupal\Core\Datetime\Element\Datetime.
      '#date_date_element' => 'none', // {'none'|'date'}
      '#date_date_format' => 'none',
      //'#date_date_callbacks' => [],
      '#date_time_element' => 'time', // {'none'|'time'|'text'}
      //'#date_time_format' => 'time', // see format_date()
      //'#date_time_callbacks' => [], // Can be used to add a jQuery timepicker or an 'All day' checkbox.
      //'#date_year_range' => '1900:2050',
      // @see Drupal\Core\Datetime\Element\DateElementBase.
      //'#date_timezone' => NULL, // new \DateTimezone(DATETIME_STORAGE_TIMEZONE),
    ];

    // #process: bottom-up.
    $info['#process'] = array_merge($parent_info['#process'], $info['#process']);
    // #validate: first OH, then Datetime.
    //$info['#element_validate'] = array_merge($parent_info['#element_validate'], $info['#element_validate']);
    //$info['#element_validate'] = array_merge($info['#element_validate'], $parent_info['#element_validate']);

    return $info + $parent_info;
  }

  /**
   * Callback for office_hours_select element.
   *
   * @param array $element
   * @param mixed $input
   * @param FormStateInterface $form_state
   * @return array|mixed|null
   *
   * Takes the #default_value and dissects it in hours, minutes and ampm indicator.
   * Mimics the date_parse() function.
   *   g = 12-hour format of an hour without leading zeros 1 through 12
   *   G = 24-hour format of an hour without leading zeros 0 through 23
   *   h = 12-hour format of an hour with leading zeros    01 through 12
   *   H = 24-hour format of an hour with leading zeros    00 through 23
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {

    if (is_string($element['#default_value'])) {
      $input['time'] = OfficeHoursDateHelper::format($element['#default_value'], 'H:i');
    }
    elseif (is_array($element['#default_value'])) {
      $input['time'] = OfficeHoursDateHelper::format($element['#default_value']['time'], 'H:i');
    }
    $input = parent::valueCallback($element, $input, $form_state);
    $element['#default_value'] = $input;

    return $input;
  }

  /**
   * Process the office_hours_select element before showing it.
   *
   * @param $element
   * @param FormStateInterface $form_state
   * @param $complete_form
   *
   * @return
   */
  public static function processOfficeHours(&$element, FormStateInterface $form_state, &$complete_form) {$element = parent::processDatetime($element, $form_state, $complete_form);

    // @todo: use $element['#date_time_callbacks'], do not use this function.
    // Adds the HTML5 attributes.
    $element['time']['#attributes'] = [
      // @todo: set a proper from/to title.
      // 'title' => t('Time (e.g. @format)', ['@format' => static::formatExample($time_format)]),
      // Fix the convention: minutes vs. seconds.
      'step' => $element['#date_increment'] * 60,
    ] + $element['time']['#attributes'];

    return $element;
  }

  /**
   * Validate the hours selector element.
   *
   * @param $element
   * @param $form_state
   */
  public static function validateOfficeHours(&$element, FormStateInterface $form_state, &$complete_form) {
    $input_exists = FALSE;

    // @todo: call validateDateTime().
    // Get the 'time' sub-array.
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    // Generate the 'object' sub-array.
    $input = parent::valueCallback($element, $input, $form_state);
    if ($input_exists) {
      //if (!empty($input['time']) && !empty($input['object'])) {
      //  parent::validateDatetime($element, $form_state, $complete_form);
      //}
    }
  }

}
