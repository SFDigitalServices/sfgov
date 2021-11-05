<?php

namespace Drupal\date_popup;

use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\views\filter\Date;

/**
 * The datetime popup views filter plugin.
 */
class DatetimePopup extends Date {

  use DatePopupTrait;

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    $this->applyDatePopupToForm($form);
  }

}
