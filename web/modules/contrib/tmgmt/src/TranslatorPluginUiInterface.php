<?php

namespace Drupal\tmgmt;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface for translator ui controllers.
 *
 * @ingroup tmgmt_translator
 */
interface TranslatorPluginUiInterface extends PluginInspectionInterface, PluginFormInterface {

  /**
   * Form callback for the checkout settings form.
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job);

  /**
   * Retrieves information about a translation job.
   *
   * Services based translators with remote states should place a Poll button
   * here to sync the job state.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The translation job.
   */
  public function checkoutInfo(JobInterface $job);

  /**
   * Form callback for the job item review form.
   */
  public function reviewForm(array $form, FormStateInterface $form_state, JobItemInterface $item);

  /**
   * Form callback for the data item element form.
   */
  public function reviewDataItemElement(array $form, FormStateInterface $form_state, $data_item_key, $parent_key, array $data_item, JobItemInterface $item);

  /**
   * Validation callback for the job item review form.
   */
  public function reviewFormValidate(array $form, FormStateInterface $form_state, JobItemInterface $item);

  /**
   * Submit callback for the job item review form.
   */
  public function reviewFormSubmit(array $form, FormStateInterface $form_state, JobItemInterface $item);

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state);

}
