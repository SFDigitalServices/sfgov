<?php

namespace Drupal\sfgov_utilities\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Defines a form that configures sfgov_search module settings
*/
class SfGovUtilitiesConfigurationForm extends ConfigFormBase {

  const SETTINGS = 'sfgov_utilities.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sfgov_utilities_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::SETTINGS,
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::SETTINGS);
    $form['#prefix'] = $this->t('This form allows you to set the google maps api key');
    $form['gmaps_api_key'] = [
      '#type' => 'textfield',
      '#size' => '50',
      '#maxlength' => '255',
      '#title' => $this->t('Google maps api key'),
      '#default_value' => $config->get('gmaps_api_key'),
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(self::SETTINGS)
      ->set('gmaps_api_key', $form_state->getValue('gmaps_api_key'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}