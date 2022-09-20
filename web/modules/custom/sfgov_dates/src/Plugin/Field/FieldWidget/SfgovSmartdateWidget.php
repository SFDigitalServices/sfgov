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
    unset($element['duration']['#title']);
    // Use our own custom value for title that it is less confusing./
    $element['duration']['#title'] = $this->t('Include end date/time?');
    // We're using the duration function as an end time toggle, the module
    // settings don't allow 'custom' to be the default value, so we're setting
    // it here.
    $element['duration']['#default_value'] = 'custom';

    return $element;
  }

}
