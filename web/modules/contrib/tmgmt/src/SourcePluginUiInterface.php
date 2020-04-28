<?php

namespace Drupal\tmgmt;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use TMGMTPluginBaseInterface;

/**
 * Interface for source ui controllers.
 *
 * @ingroup tmgmt_source
 */
interface SourcePluginUiInterface extends PluginInspectionInterface {

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
   *
   * @see tmgmt_views_default_views().
   */
  public function hook_views_default_views();

}
