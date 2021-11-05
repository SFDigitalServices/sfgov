<?php

namespace Drupal\date_popup;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Date;

/**
 * The date popup views filter plugin.
 */
class DatePopup extends Date {

  use DatePopupTrait;

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    $this->applyDatePopupToForm($form);
  }

}
