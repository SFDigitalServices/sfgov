<?php

namespace Drupal\sfgov_pages\mohcd\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class CalculatorSettingsForm extends ConfigFormBase {

  const SETTINGS = 'sfgov_pages.mohcd.calculator_settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mohcd_calculator_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sfgov_pages.mohcd.calculator_settings',
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::SETTINGS);

    $form['#prefix'] = $this->t('This form allows you to associate AMI with year.  These values will be used for MOHCD\'s estimated valuation for inclusionary homes calculator.');

    $form['currentYearAMI'] = [
      '#id' => 'currentYearAMI',
      '#type' => 'textfield',
      '#title' => t('Current year AMI'),
      '#required' => TRUE,
      '#default_value' => $config->get('currentYearAMI'),
    ];

    $form['yearAMI'] = [
      '#id' => 'yearAMI',
      '#type' => 'textarea',
      '#title' => t('Year AMI values'),
      '#rows' => 35,
      '#description' => t('Enter values as YEAR|AMI.  One value per line.'),
      '#description_display' => 'before',
      '#required' => TRUE,
      '#default_value' => $config->get('yearAMI'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $yearAMIValue = $form_state->getValue('yearAMI') ?? '';
    
    if (!empty($yearAMIValue)) {
      $yearAMIItems = preg_split('/\r\n|\r|\n/', $yearAMIValue);

      $hasError = false;
      $errors = [];
      
      foreach($yearAMIItems as $yearAMIItem) {
        $value = explode("|", $yearAMIItem);
        if (empty($value) 
            || count($value) !== 2 
            || empty(trim($value[0]))
            || empty(trim($value[1]))
            || !is_numeric(trim($value[0]))
            || !is_numeric(trim($value[1]))) {
          $errors[] = "<li>$yearAMIItem</li>";
        }
      }

      if (!empty($errors)) {
        $errorMsg = new TranslatableMarkup('Some values are not properly formatted:<br><ul>' . implode($errors) . '</ul>');
        $form_state->setErrorByName('yearAMI', $errorMsg);
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(self::SETTINGS)
      ->set('yearAMI', $form_state->getValue('yearAMI'))
      ->set('currentYearAMI', $form_state->getValue('currentYearAMI'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
