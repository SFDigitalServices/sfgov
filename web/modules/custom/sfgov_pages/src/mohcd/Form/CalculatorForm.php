<?php

namespace Drupal\sfgov_pages\mohcd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CalculatorForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mohcd_calculator_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('sfgov_pages.mohcd.calculator_settings');
    $yearAMIValues = $config->get('yearAMI');
    $yearAMIArray = preg_split('/\r\n|\r|\n/', $yearAMIValues);
    $options = [];

    foreach($yearAMIArray as $yearAMI) {
      $value = explode("|", $yearAMI);
      $options[trim($value[1])] = trim($value[0]);
    }

    $form['purchasePrice'] = [
      '#id' => 'purchasePrice',
      '#type' => 'textfield',
      '#title' => t('What is the purchase price?'),
      '#field_suffix' => '<div id="purchasePriceError" class="hidden">Please enter a valid number (no $ sign)</div>',
      '#required' => TRUE,
    ];

    $form['purchaseYear'] = [
      '#id' => 'purchaseYear',
      '#type' => 'select',
      '#title' => t('What is the purchase year?'),
      '#options' => $options,
      '#field_suffix' => '<div id="purchaseYearError" class="hidden">Please select a purchase year</div>',
      '#required' => TRUE,
    ];

    $form['btnCalc'] = [
      '#id' => 'btnCalc',
      '#type' => 'button',
      '#value' => t('Calculate'),
    ];

    $form['bmrValuation'] = [
      '#id' => 'bmrValuation',
      '#type' => 'textfield',
      '#title' => t('Your current BMR valuation:'),
      '#field_suffix' => '<div id="valuationError" class="hidden">Please check fields for valid values (all fields are required)</div>',
    ];

    $form['#attached']['library'][] = 'sfgov_pages/mohcd_calculator';
    $form['#attached']['drupalSettings']['sfgov']['mohcd']['calculator']['currentYearAMI'] = $config->get('currentYearAMI');

    return $form;
  }

  // no validation handled in src/mohcd/js/mohcd-calculator.js
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  // no submission, calculation handled in src/mohcd/js/mohcd-calculator.js
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
