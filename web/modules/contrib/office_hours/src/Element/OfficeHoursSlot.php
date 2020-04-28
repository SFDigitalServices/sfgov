<?php

namespace Drupal\office_hours\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Provides a one-line text field form element.
 *
 * @FormElement("office_hours_slot")
 */
class OfficeHoursSlot extends OfficeHoursList {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo();
  }

  /**
   * Process an individual element.
   *
   * Build the form element. When creating a form using FAPI #process,
   * note that $element['#value'] is already set.
   */
  public static function processOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {
    // Fill with default data from a List element.
    $element = parent::processOfficeHoursSlot($element, $form_state, $complete_form);
    // @todo D8: $form_state = ...
    // @todo D8: $form = ...
    // @todo D8: $this->t()

    $maxdelta = $element['#field_settings']['cardinality_per_day'] - 1;
    $daydelta = $element['#daydelta'];
    if ($daydelta == 0) {
      // This is the first block of the day.
      $label = $element['#dayname']; // Show Day name (already translated) as label.
      $slot_style = '';
      $slot_classes[] = 'office-hours-slot'; // Show the slot.
    }
    elseif ($daydelta > $maxdelta) {
      // Never show this illegal slot.
      // In case the number of slots per day was lowered by admin, this element
      // may have a value. Better clear it (in case a value was entered before).
      // The value will be removed upon the next 'Save' action.
      $label = '';
      // The following style is only needed if js isn't working.
      $slot_style = 'style = "display:none;"';
      // The following class is the trigger for js to hide the row.
      $slot_classes[] = 'office-hours-hide';

      $element['#value'] = empty($element['#value'] ? [] : $element['#value']);
      $element['#value']['starthours'] = '';
      $element['#value']['endhours'] = '';
      $element['#value']['comment'] = NULL;
    }
    elseif (!empty($element['#value']['starthours'])) {
      // This is a following block with contents.
      $label = t('and');
      $slot_style = '';
      $slot_classes[] = 'office-hours-slot'; // Show the slot.
      $slot_classes[] = 'office-hours-more'; // Show add-link.
    }
    else {
      // This is an empty following slot.
      $label = t('and');
      $slot_style = 'style = "display:none;"';
      $slot_classes[] = 'office-hours-hide'; // Hide the slot.
      $slot_classes[] = 'office-hours-more'; // Add the add-link, in case shown by js.
    }
    $element['#attributes'] = ['class' => $slot_classes];

    // Copied from EntityListBuilder::buildOperations().
    //$element['#value']['operations'] = $this->buildOperations($entity);
    //$element['#value']['operations'] = [
    //  '#type' => 'operations',
    //  '#links' => self::getDefaultOperations($entity = NULL),
    //];

    // Overwrite the 'day' select-field.
    $day_number = $element['#day'];
    $element['day'] = [
      '#type' => 'hidden',
      '#prefix' => $daydelta ? "<div class='office-hours-more-label'>$label</div>" : "<div class='office-hours-label'>$label</div>",
      '#default_value' => $day_number,
    ];
    $element['#attributes']['class'][] = "office-hours-day-$day_number";

    return $element;
  }

  /**
   * Render API callback: Validates the office_hours_slot element.
   *
   * Implements a callback for _office_hours_elements().
   *
   * For 'office_hours_slot' (day) and 'office_hours_datelist' (hour) elements.
   * You can find the value in $element['#value'], but better in $form_state['values'],
   * which is set in validateOfficeHoursSlot().
   */
  //public static function validateOfficeHoursSlot(&$element, FormStateInterface $form_state, &$complete_form) {
  //   return parent::validateOfficeHoursSlot($element, $form_state, $complete_form);
  //}

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected static function getDefaultOperations($element) {
    $operations = [];
    $operations['copy'] = [];
    $operations['delete'] = [];
    $operations['add'] = [];
    $suffix = ' ';

    $maxdelta = $element['#field_settings']['cardinality_per_day'] - 1;
    $daydelta = $element['#daydelta'];

    // Show a 'Clear this line' js-link to each element.
    // Use text 'Remove', which has lots of translations.
    $operations['delete'] = [];
    if (isset($element['#value']['starthours']) || isset($element['#value']['endhours'])) {
      $operations['delete'] = [
        '#type' => 'link',
        //'#title' => t('Delete'),
        '#title' => t('Remove'),
        '#weight' => 12,
        '#url' => Url::fromRoute('<front>'), // dummy-url, will be catched by javascript.
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-delete-link', ],
        ],
      ];
    }

    // Add 'Copy' link to first slot of each day; first day copies from last day.
    // @todo: $this->t()
    $operations['copy'] = [];
    if ($daydelta == 0) {
      $operations['copy'] = [
        '#type' => 'link',
        '#title' => t('Same as above'),
        '#title' => t(($element['#day'] !== OfficeHoursDateHelper::getFirstDay() && $daydelta == 0) ? 'Copy previous day' : 'Copy last day') . ' ',
        '#weight' => 16,
        '#url' => Url::fromRoute('<front>'), // dummy-url, will be catched by javascript.
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-copy-link', ],
        ],
      ];
    }

    // Add 'Add time slot' link to all-but-last slots of each day.
    $operations['add'] = [];
    if ($daydelta < $maxdelta) {
      $operations['add'] = [
        '#type' => 'link',
        '#title' => t('Add @node_type', ['@node_type' => t('time slot'), ]),
        '#weight' => 11,
        '#url' => Url::fromRoute('<front>'), // dummy-url, will be catched by javascript.
        '#suffix' => $suffix,
        '#attributes' => [
          'class' => ['office-hours-add-link', ],
        ],
      ];
    }

    return $operations;
  }

}
