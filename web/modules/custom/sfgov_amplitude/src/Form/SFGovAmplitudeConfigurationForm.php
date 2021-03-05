<?php

namespace Drupal\sfgov_amplitude\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Defines a form that configures sfgov_search module settings
*/
class SFGovAmplitudeConfigurationForm extends ConfigFormBase {

  const SETTINGS = 'sfgov_amplitude.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sfgov_amplitude_admin_settings';
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
    
    $form['#prefix'] = $this->t('This form allows you to set the amplitude api key');
    
    $form['amplitude_api_key'] = [
      '#type' => 'textfield',
      '#size' => '50',
      '#maxlength' => '255',
      '#title' => $this->t('Amplitude api key'),
      '#default_value' => $config->get('amplitude_api_key'),
    ];

    $form['amplitude_drupal_tokens'] = [
      '#type' => 'textarea',
      '#rows' => 40,
      '#title' => $this->t('Drupal tokens'),
      '#description' => $this->t(
        'The JSON-formatted set of drupal tokens to expose to amplitude'
      ),
      '#default_value' => $config->get('amplitude_drupal_tokens'),
    ];

    $form['token_container']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => 'all',
      '#show_restricted' => TRUE,
      '#weight' => 90,
    ];

    return parent::buildForm($form, $form_state);
  }

    /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $amplitude_drupal_tokens = $form_state->getValue('amplitude_drupal_tokens');
    if ($amplitude_drupal_tokens && !json_decode($amplitude_drupal_tokens)) {
      $form_state->setErrorByName('amplitude_drupal_tokens', $this->t('Entered JSON is in invalid format!'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config(self::SETTINGS)
      ->set('amplitude_api_key', $form_state->getValue('amplitude_api_key'))
      ->set('amplitude_drupal_tokens', $form_state->getValue('amplitude_drupal_tokens'))
      ->save();
  }
}