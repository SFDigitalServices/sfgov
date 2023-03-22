<?php

namespace Drupal\sfgov_pages\mohcd\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use NumberFormatter;

/**
 * MOHCD Calculator form.
 */
class CalculatorForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mohcd_calculator_form';
  }

  /**
   * Return the calculator label
   */
  public function getLabel() {
    return \Drupal::state()->get('sfgov_pages_mohcd_label') ?? t('Calculate');
  }

  /**
   * Return the calculator description/help text
   */
  public function getDescription() {
    return \Drupal::state()->get('sfgov_pages_mohcd_description') ?? t('Your purchase information can be found in the Promissory Note and closing documents.');
  }

  /**
   * Return the current AMI
   */
  public function getCurrentYearAMI() {
    return \Drupal::state()->get('sfgov_pages_mohcd_currentYearAMI');
  }

  /**
   * Get the complete year|AMI list
   */
  public function getYearAMIList(): array {
    $options = [];
    $yearAMI = \Drupal::state()->get('sfgov_pages_mohcd_yearAMI');
    $yearAMIArray = preg_split('/\r\n|\r|\n/', $yearAMI);
    foreach($yearAMIArray as $yearAMI) {
      $value = explode("|", $yearAMI);
      $options[trim($value[0])] = trim($value[1]);
    }
    return $options;
  }

  /**
   * Get the least recent (earliest) year from the AMI list
   */
  public function getEarliestYear() {
    $options = $this->getYearAMIList();
    $years = array_flip($options);
    return min($years);
  }

  /**
   * Get the most recent (latest) year from the AMI list
   */
  public function getLatestYear() {
    $options = $this->getYearAMIList();
    $years = array_flip($options);
    return max($years);
  }

  /**
   * Return the AMI value for the given year.
   */
  public function getYearAMI($year) {
    $options = $this->getYearAMIList();
    return $options[$year] ?? FALSE;
  }

  /**
   * Build the calculator.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currentYearAMI = $this->getCurrentYearAMI();

    $form['#tree'] = TRUE;

    $form['bmrCalculator'] = array(
      '#type'  => 'fieldset',
      '#title' => t('@label', ['@label' => $this->getLabel()]),
      '#description' => t('@description', ['@description' => $this->getDescription()]),
      '#description_display' => 'before',
    );

    $form['bmrCalculator']['purchasePrice'] = [
      '#id' => 'purchasePrice',
      '#type' => 'textfield',
      '#title' => t('Purchase price'),
      '#prefix' => '<div id="purchasePriceWrapper">',
      '#field_suffix' => '<div id="purchasePriceError"></div>',
      '#suffix' => '</div>',
      '#attributes' => [
        'inputmode' => 'numeric',
        'pattern' => '[0-9]*',
        'minlength' => '4',
        'maxlength' => '12',
      ],
    ];

    $form['bmrCalculator']['purchaseYear'] = [
      '#id' => 'purchaseYear',
      '#type' => 'textfield',
      '#title' => t('Purchase year'),
      '#description' => t('Enter a year between @firstyear and @latestyear', [
        '@firstyear' => $this->getEarliestYear(),
        '@latestyear' => $this->getLatestYear()
      ]),
      '#prefix' => '<div id="purchaseYearWrapper">',
      '#field_suffix' => '<div id="purchaseYearError"></div>',
      '#suffix' => '</div>',
      '#attributes' => [
        'inputmode' => 'numeric',
        'minlength' => '4',
        'maxlength' => '4',
        'pattern' => '[0-9]*',
        'min' => $this->getEarliestYear(),
        'max' => $this->getLatestYear(),
      ],
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
          'type' => 'none',
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

  /**
   * Calculate the BMR Value and display on the form.
   */
  public function calculateBMRValuation(array $form, FormStateInterface $form_state): AjaxResponse {
    $ajax_response = new AjaxResponse();

    $has_error = FALSE;
    $currentYearAMI = $this->getCurrentYearAMI();
    $values = $form_state->getValues();
    $purchasePrice = $values['bmrCalculator']['purchasePrice'];
    $purchaseYear = (int) $values['bmrCalculator']['purchaseYear'];
    $purchaseYearAMI = $this->getYearAMI($purchaseYear);

    // Assert the purchasePrice is valid
    $ajax_response->addCommand(new HtmlCommand('#purchasePriceError', ''));
    if (!$purchasePrice || !is_numeric($purchasePrice)) {
      $has_error = TRUE;
      $ajax_response->addCommand(new HtmlCommand('#purchasePriceError', t("Price must be in numbers only.")));
    }

    // Assert the purchaseYear is valid
    $ajax_response->addCommand(new HtmlCommand('#purchaseYearError', ''));
    if (!$purchaseYearAMI || !is_numeric($purchaseYearAMI)) {
      $has_error = TRUE;
      $ajax_response->addCommand(new HtmlCommand('#purchaseYearError ', t("Year must be 4 numbers.")));
    }

    // Assert the purchaseYear must be between the earliest and latest years. ##UNCOMMENT BELOW TO TRIGGER VALIDATION.
//    if ($purchaseYear && is_numeric($purchaseYear) && ($purchaseYear < $this->getEarliestYear() || $purchaseYear > $this->getLatestYear())) {
//      $has_error = TRUE;
//      $ajax_response->addCommand(new HtmlCommand('#purchaseYearError ', t('Year must be between @firstyear and @latestyear', [
//        '@firstyear' => $this->getEarliestYear(),
//        '@latestyear' => $this->getLatestYear()
//      ])));
//    }

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

  // no validation handled in src/mohcd/js/mohcd-calculator.js
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  // no submission, calculation handled in src/mohcd/js/mohcd-calculator.js
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
