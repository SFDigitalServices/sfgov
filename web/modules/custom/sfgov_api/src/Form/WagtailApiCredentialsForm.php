<?php

namespace Drupal\sfgov_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure sfgov_api settings for this site.
 */
class WagtailApiCredentialsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sfgov_api_wagtail_api_credentials';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sfgov_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->config('sfgov_api.settings')->get('username'),
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $this->config('sfgov_api.settings')->get('password'),
    ];
    $form['host_ip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host IP'),
      '#default_value' => $this->config('sfgov_api.settings')->get('host_ip'),
    ];
    $form['port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#default_value' => $this->config('sfgov_api.settings')->get('port'),
    ];
    $form['use_port'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Port'),
      '#default_value' => $this->config('sfgov_api.settings')->get('use_port'),
    ];
    $form['protocol'] = [
      '#type' => 'radios',
      '#title' => $this->t('Which protocol to use'),
      '#options' => [
        'https://' => $this->t('https'),
        'http://' => $this->t('http'),
      ],
      '#default_value' => $this->config('sfgov_api.settings')->get('protocol'),
    ];
    $form['api_url_base'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL Base'),
      '#description' => $this->t('Save the form to generate this value.'),
      '#disabled' => TRUE,
      '#default_value' => $this->config('sfgov_api.settings')->get('api_url_base'),
      '#disabled' => TRUE,
    ];

    $form['wag_parent'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Wagtail Parent IDs'),
      '#description' => $this->t('These are the Wagtail page IDs for the parent pages of the translated pages.'),
    ];
    $form['wag_parent']['wag_parent_en'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wagtail Parent ID for English'),
      '#default_value' => $this->config('sfgov_api.settings')->get('wag_parent_en'),
    ];
    $form['wag_parent']['wag_parent_es'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wagtail Parent ID for Spanish'),
      '#default_value' => $this->config('sfgov_api.settings')->get('wag_parent_es'),
    ];
    $form['wag_parent']['wag_parent_fil'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wagtail Parent ID for Filipino'),
      '#default_value' => $this->config('sfgov_api.settings')->get('wag_parent_fil'),
    ];
    $form['wag_parent']['wag_parent_zh_hant'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wagtail Parent ID for Chinese'),
      '#default_value' => $this->config('sfgov_api.settings')->get('wag_parent_zh_hant'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Manually assemble a URL base for the API and store it with the config.
    $use_port = $form_state->getValue('use_port');
    $api_url_base = $form_state->getValue('protocol') . $form_state->getValue('host_ip') . ($use_port ? ':' . $form_state->getValue('port') : '') . '/api/cms/';
    $this->config('sfgov_api.settings')
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->set('host_ip', $form_state->getValue('host_ip'))
      ->set('use_port', $form_state->getValue('use_port'))
      ->set('protocol', $form_state->getValue('protocol'))
      ->set('port', $form_state->getValue('port'))
      ->set('wag_parent_en', $form_state->getValue('wag_parent_en'))
      ->set('wag_parent_es', $form_state->getValue('wag_parent_es'))
      ->set('wag_parent_fil', $form_state->getValue('wag_parent_fil'))
      ->set('wag_parent_zh_hant', $form_state->getValue('wag_parent_zh_hant'))
      ->set('api_url_base', $api_url_base)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
