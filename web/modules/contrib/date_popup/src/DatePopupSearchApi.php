<?php

namespace Drupal\date_popup;


use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\filter\SearchApiDate;

/**
 * Defines a class for search api date filter with popup.
 */
class DatePopupSearchApi extends SearchApiDate {

  use DatePopupTrait;

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    $this->applyDatePopupToForm($form);
  }

}
