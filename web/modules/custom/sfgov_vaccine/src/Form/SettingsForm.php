<?php

namespace Drupal\sfgov_vaccine\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sfgov_vaccine.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vaccine_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sfgov_vaccine.settings');

    $form['base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Base url'),
      '#description' => $this->t('Enter the base url (e.g. https://vaccination-site-microservice.vercel.app, https://vaccination-site-microservice-git-automate-site-data-sfds.vercel.app).'),
      '#default_value' => $config->get('base_url')
    ];
    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query'),
      '#description' => $this->t('Enter the url query (e.g. api/v1/appointments).'),
      '#default_value' => $config->get('query'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    UrlHelper::isValid($values['base_url']);

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('sfgov_vaccine.settings')
      ->set('base_url', $form_state->getValue('base_url'))
      ->set('query', $form_state->getValue('query'))
      ->save();
  }

}
