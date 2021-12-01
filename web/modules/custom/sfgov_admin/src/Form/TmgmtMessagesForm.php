<?php

namespace Drupal\sfgov_admin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure tmgmt logs settings form.
 */
class TmgmtMessagesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['tmgmt.sfgov_admin_logs'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sfgov_admin_tmgmt_logs_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tmgmt.sfgov_admin_logs');
    $form['purge_frequency'] = [
      '#type'        => 'select',
      '#title'       => t('Purge job item logs'),
      '#description' => t('If configured, translation jobs item logs will be removed on set frequency.'),
      '#options'     => [
        '_never'  => t('Never'),
        '1'       => t('As soon as possible'),
        '86400'   => t('After 24 hours'),
        '86400'   => t('After 1 days'),
        '259200'  => t('After 3 days'),
        '604800'  => t('After 7 days'),
        '2592000' => t('After 30 days'),
      ],
      '#default_value' => $config->get('purge_frequency'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tmgmt.sfgov_admin_logs')
      ->set('purge_frequency', $form_state->getValue('purge_frequency'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
