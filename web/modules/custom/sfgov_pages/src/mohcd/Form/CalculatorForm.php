<?php

namespace Drupal\sfgov_pages\mohcd\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use NumberFormatter;

class CalculatorForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mohcd_calculator_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $currentYearAMI = \Drupal::state()->get('sfgov_pages_mohcd_currentYearAMI');
    $yearAMI = \Drupal::state()->get('sfgov_pages_mohcd_yearAMI');
    $yearAMIArray = preg_split('/\r\n|\r|\n/', $yearAMI);

    $options = ['- Select year -'];
    foreach($yearAMIArray as $yearAMI) {
      $value = explode("|", $yearAMI);
      $options[trim($value[1])] = trim($value[0]);
    }

    $form['#tree'] = TRUE;

    $form['bmrCalculator'] = array(
      '#type'  => 'fieldset',
      '#title' => $this->t('Calculate'),
      '#description' => $this->t('Your purchase information can be found in the Promissory Note and closing documents.'),
      '#description_display' => 'before',
    );

    $form['bmrCalculator']['purchasePrice'] = [
      '#id' => 'purchasePrice',
      '#type' => 'textfield',
      '#title' => t('Purchase price?'),
      '#field_prefix' => '<div id="purchasePriceError"></div>',
    ];

    $form['bmrCalculator']['purchaseYear'] = [
      '#id' => 'purchaseYear',
      '#type' => 'select',
      '#title' => t('Purchase year?'),
      '#options' => $options,
      '#description' => t('Enter a year between 1996 and 2022'),
      '#field_prefix' => '<div id="purchaseYearError"></div>',
    ];

    $form['bmrCalculator']['btnCalc'] = [
      '#id' => 'btnCalc',
      '#type' => 'button',
      '#value' => t('Calculate'),
      '#attributes' => [
        'class' => ['button button-primary'],
      ],
      '#ajax' => [
        'callback' => '::calculateBMRValuation',
        'effect' => 'fade',
        'progress' => array(
          'type' => 'throbber',
          'message' => t('Calculating...'),
        ),
      ],
    ];

    $form['bmrCalculator']['bmrValuation'] = [
      '#type' => 'markup',
      '#markup'=> '<div id="bmrValuation"></div> ',
    ];

    $form['#attached']['library'][] = 'sfgov_pages/mohcd_calculator';
    $form['#attached']['drupalSettings']['sfgov']['mohcd']['calculator']['currentYearAMI'] = $currentYearAMI;

    $form['#attributes']['class'][] = 'sfgov-section__content';

    $form['#cache']['max-age'] = 0;

    return $form;
  }

  // no validation handled in src/mohcd/js/mohcd-calculator.js
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  // no submission, calculation handled in src/mohcd/js/mohcd-calculator.js
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  public function calculateBMRValuation(array $form, FormStateInterface $form_state): AjaxResponse {
    $ajax_response = new AjaxResponse();

    $currentYearAMI = \Drupal::state()->get('sfgov_pages_mohcd_currentYearAMI');
    $has_error = FALSE;
    $values = $form_state->getValues();
    $purchasePrice = $values['bmrCalculator']['purchasePrice'];
    $purchaseYearAMI = $values['bmrCalculator']['purchaseYear'];

    // Assert the purchasePrice is valid
    $ajax_response->addCommand(new HtmlCommand('#purchasePriceError', ''));
    if (!$purchasePrice || !is_numeric($purchasePrice)) {
      $has_error = TRUE;
      $ajax_response->addCommand(new HtmlCommand('#purchasePriceError', t("Please enter a valid number.")));
    }

    // Assert the purchaseYear is valid
    $ajax_response->addCommand(new HtmlCommand('#purchaseYearError', ''));
    if (!$purchaseYearAMI || empty($purchaseYearAMI)) {
      $has_error = TRUE;
      $ajax_response->addCommand(new HtmlCommand('#purchaseYearError ', t("Please select a purchase year.")));
    }

    if (!$has_error) {
      $value = (int) round($purchasePrice + ($purchasePrice * (($currentYearAMI - $purchaseYearAMI) / $purchaseYearAMI)));

      $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
      $value = $formatter->formatCurrency($value, 'USD');

      $text = [];

      $text['label'] = [
        '#type' => 'markup',
        '#prefix' => '<div id="bmrValuationLabel">',
        '#markup' => t('Your current BMR valuation:'),
        '#suffix' => '</div>',
      ];

      $text['value'] = [
        '#type' => 'markup',
        '#prefix' => '<div id="bmrValuationResults">',
        '#markup' => $value,
        '#suffix' => '</div>',
      ];

      $ajax_response->addCommand(new HtmlCommand('#bmrValuation', \Drupal::service('renderer')->render($text)));
    } else {
      $ajax_response->addCommand(new HtmlCommand('#bmrValuation', ''));
    }

    return $ajax_response;
  }
}
