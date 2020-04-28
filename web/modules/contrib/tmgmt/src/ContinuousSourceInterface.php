<?php

namespace Drupal\tmgmt;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\Entity\Job;

/**
 * Interface for continuous sources.
 *
 * @ingroup tmgmt_source
 */
interface ContinuousSourceInterface {

  /**
   * Creates "Continuous settings" form element.
   *
   * @param array $form
   *   The job form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\tmgmt\Entity\Job $job
   *   Continuous job.
   *
   * @return array
   *   Continuous settings form elements
   */
  public function continuousSettingsForm(array &$form, FormStateInterface $form_state, Job $job);


  /**
   * Checks whether the continuous job item should be created.
   *
   * @param \Drupal\tmgmt\Entity\Job $job
   *   Continuous job.
   * @param string $plugin
   *   The plugin name.
   * @param string $item_type
   *   The source item type.
   * @param string $item_id
   *   The source item id.
   *
   * @return bool
   *   TRUE if continuous job item should be created, FALSE if not.
   */
  public function shouldCreateContinuousItem(Job $job, $plugin, $item_type, $item_id);

}
