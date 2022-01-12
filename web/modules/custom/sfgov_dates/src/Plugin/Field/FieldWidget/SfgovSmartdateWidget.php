<?php

namespace Drupal\sfgov_dates\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_date\Plugin\Field\FieldWidget\SmartDateDefaultWidget;
/**
 * Defines the 'sfgov_dates_customsmartdate' field widget.
 *
 * @FieldWidget(
 *   id = "sfgov_dates_customsmartdate",
 *   label = @Translation("SFgov SmartDate"),
 *   field_types = {
 *     "smartdate",
 *     "daterange"
 *   }
 * )
 */
class SfgovSmartdateWidget extends SmartDateDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    unset($element["duration"]["#title"]);
    $element["duration"]["#title"] = t('End Time');
    return $element;
    // Note, could do this with a widget alter if we don't have any other
    // reason for a custom widget.
  }

}
