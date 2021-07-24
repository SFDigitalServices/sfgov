<?php

namespace Drupal\sfgov_media\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for sfgov_media.
 */
class SFGovMediaConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sfgov_media_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sfgov_media.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('sfgov_media.settings');

    $form['power_bi'] = [
      '#title' => $this->t('Power BI'),
      '#type' => 'fieldset',
      '#description' => $this->t('Settings for Power BI embeds.')
    ];

    $form['power_bi']['powerbi_kbd_instructions'] = [
      '#title' => $this->t('Keyboard instructions'),
      '#type' => 'textarea',
      '#rows' => 15,
      '#default_value' => $config->get('powerbi_kbd_instructions'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('sfgov_media.settings')
      ->set('powerbi_kbd_instructions', $form_state->getValue('powerbi_kbd_instructions'))
      ->save();
  }

}
