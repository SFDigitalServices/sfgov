<?php

namespace Drupal\amplitude\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class AmplitudeConfigForm.
 */
class AmplitudeConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'amplitude.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amplitude_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('amplitude.settings');
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('Your amplitude API key.'),
      '#maxlength' => 32,
      '#size' => 32,
      '#default_value' => $config->get('api_key'),
    ];

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug'),
      '#default_value' => $config->get('debug'),
    ];

    $config_options_link = Link::fromTextAndUrl(
      $this->t('Amplitude configuration options'),
      Url::fromUri('https://help.amplitude.com/hc/en-us/articles/115001361248#settings-configuration-options')
    )->toString();
    $form['config_options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional Amplitude configuration options'),
      '#description' => $this->t(
        'The JSON-formatted configuration options as described by the @config_options_link documentation',
        ['@config_options_link' => $config_options_link]
      ),
      '#default_value' => $config->get('config_options'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $config_options = $form_state->getValue('config_options');
    if ($config_options && !json_decode($config_options)) {
      $form_state->setErrorByName('config_options', $this->t('Entered JSON is in invalid format!'));
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('amplitude.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('debug', $form_state->getValue('debug'))
      ->set('config_options', $form_state->getValue('config_options'))
      ->save();
  }

}
