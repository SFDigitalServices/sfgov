<?php

namespace Drupal\sfgov_formio\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration settings for SF.gov Form.io module.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sfgov_formio_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sfgov_formio.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sfgov_formio.settings');

    $form['formio'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configure Form.io Versions'),
    ];
    $form['formio']['info'] = [
      '#type' => 'item',
      '#markup' => 'Use the fields below to specify the versions used for each dependency.',
    ];
    $form['formio']['formio_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('<a href="https://github.com/formio/formio.js">Formio.js</a> version'),
      '#description' => $this->t('Specify a version number, e.g. <code>4.10.0-rc.6</code>, or leave blank to use default (latest release).'),
      '#default_value' => $config->get('formio_version'),
    ];
    $form['formio']['formio_sfds_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('<a href="https://github.com/SFDigitalServices/formio-sfds">Form.io SFDS</a> version'),
      '#description' => $this->t('Specify a version number, e.g. <code>7.0.0</code>, or leave blank to use default (latest release).'),
      '#default_value' => $config->get('formio_sfds_version'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve and save the configuration.
    $this->configFactory->getEditable('sfgov_formio.settings')
      ->set('formio_version', trim($form_state->getValue('formio_version')))
      ->set('formio_sfds_version', trim($form_state->getValue('formio_sfds_version')))
      ->save();

    // Invalidate library definitions, so they get rebuilt based on settings.
    Cache::invalidateTags(['library_info']);

    parent::submitForm($form, $form_state);
  }

}
