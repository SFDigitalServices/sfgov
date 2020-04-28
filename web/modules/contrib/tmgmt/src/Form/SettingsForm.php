<?php

namespace Drupal\tmgmt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure tmgmt settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return array('tmgmt.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tmgmt.settings');
    $form['workflow'] = array(
      '#type' => 'details',
      '#title' => t('Workflow settings'),
      '#open' => TRUE,
    );
    $form['workflow']['tmgmt_quick_checkout'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow quick checkout'),
      '#description' => t("Enabling this will skip the checkout form and instead directly process the translation request in cases where there is only one translator available which doesn't provide any additional configuration options."),
      '#default_value' => $config->get('quick_checkout'),
    );
    $form['performance'] = array(
      '#type' => 'details',
      '#title' => t('Performance settings'),
      '#open' => TRUE,
    );
    $form['performance']['tmgmt_purge_finished'] = array(
      '#type' => 'select',
      '#title' => t('Purge finished jobs'),
      '#description' => t('If configured, translation jobs that have been marked as finished will be purged after a given time. The translations itself will not be deleted.'),
      '#options' => [
        '_never' => t('Never'),
        '0' => t('Immediately'),
        '86400' => t('After 24 hours'),
        '604800' => t('After 7 days'),
        '2592000' => t('After 30 days'),
        '31536000' => t('After 365 days'),
      ],
      '#default_value' => $config->get('purge_finished'),
    );
    $form['security'] = array(
      '#type' => 'details',
      '#title' => t('Security settings'),
      '#open' => TRUE,
    );
    $form['security']['tmgmt_anonymous_access'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow access to source for translators'),
      '#description' => t('Enabling this will give translators and anyone with access to jobs access to view all content, including unpublished and other protected content.'),
      '#default_value' => $config->get('anonymous_access'),
    );
    $form['performance']['tmgmt_submit_job_item_on_cron'] = array(
      '#type' => 'checkbox',
      '#title' => t('Submit continuous job items on cron'),
      '#description' => t('Continuous job items are submitted in groups on cron runs. Otherwise they are submitted immediately when content is created.'),
      '#default_value' => $config->get('submit_job_item_on_cron'),
    );
    $form['performance']['job_items_cron_limit'] = array(
      '#type' => 'number',
      '#title' => t('Number of job items to process on cron'),
      '#description' => t('The number of job items that should be processed in one cron run. Depending on the chosen translation provider, increasing the number of job items could make translation projects bigger and slower to process.'),
      '#default_value' => $config->get('job_items_cron_limit'),
      '#min' => 1,
      '#states' => array(
        'visible' => array(
          ':input[name="tmgmt_submit_job_item_on_cron"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['text_formats'] = [
      '#type' => 'details',
      '#title' => t('Text format settings'),
      '#open' => TRUE,
    ];
    $form['text_formats']['respect_text_format'] = array(
      '#type' => 'checkbox',
      '#title' => t('Respect text format'),
      '#description' => t("Disabling will force all textareas to plaintext. No editors will be shown."),
      '#default_value' => $config->get('respect_text_format'),
    );

    $options = array();
    foreach (filter_formats() as $format) {
      $options[$format->id()] = $format->label();
    }

    $form['text_formats']['allowed_formats'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Allowed formats'),
      '#description' => t("Allows to prevent content with a certain text format from being translated. If none are selected, all are allowed."),
      '#options' => $options,
      '#default_value' => (array) $config->get('allowed_formats'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tmgmt.settings')
      ->set('quick_checkout', $form_state->getValue('tmgmt_quick_checkout'))
      ->set('purge_finished', $form_state->getValue('tmgmt_purge_finished'))
      ->set('anonymous_access', $form_state->getValue('tmgmt_anonymous_access'))
      ->set('respect_text_format', $form_state->getValue('respect_text_format'))
      ->set('allowed_formats', array_keys(array_filter($form_state->getValue('allowed_formats'))))
      ->set('submit_job_item_on_cron', $form_state->getValue('tmgmt_submit_job_item_on_cron'))
      ->set('job_items_cron_limit', $form_state->getValue('job_items_cron_limit'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
