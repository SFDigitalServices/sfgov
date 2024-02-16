<?php

namespace Drupal\sfgov_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Defines a form that configures sfgov_search module settings
*/
class SfGovGoogleSearchConfigurationForm extends ConfigFormBase {

  const SETTINGS = 'sfgov_google_search.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sfgov_google_search_admin_settings';
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

    $form['details'] = [
      '#type' => 'fieldset',
      '#title' => t('Configuration'),
      '#description' => t('This form allows you to configure google search settings.'),
      '#open' => TRUE,
    ];

    $form['details']['enable'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable'),
      '#default_value' => $config->get('enable'),
    ];

    $form['details']['cx'] = [
      '#type' => 'textfield',
      '#title' => t('Search Engine ID'),
      '#default_value' => $config->get('cx'),
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(self::SETTINGS)
      ->set('enable', $form_state->getValue('enable'))
      ->set('cx', $form_state->getValue('cx'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
