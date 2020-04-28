<?php

namespace Drupal\tmgmt_local\Plugin\tmgmt\Translator;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Session\AccountInterface;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt_local\Entity\LocalTask;
use Drupal\tmgmt_local\LocalTaskInterface;

/**
 * Drupal user provider.
 *
 * @TranslatorPlugin(
 *   id = "local",
 *   label = @Translation("Drupal user"),
 *   description = @Translation("Allows local users to process translation jobs."),
 *   ui = "\Drupal\tmgmt_local\LocalTranslatorUi",
 *   default_settings = {},
 *   map_remote_languages = FALSE
 * )
 */
class LocalTranslator extends TranslatorPluginBase implements ContinuousTranslatorInterface {

  protected $language_pairs = array();

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $items = $job->getItems();
    $this->requestJobItemsTranslation($items);

    // The translation job has been successfully submitted.
    $job->submitted();
  }

  /**
   * {@inheritdoc}
   */
  public function requestJobItemsTranslation(array $job_items) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = reset($job_items)->getJob();
    $tuid = $job->getSetting('translator');

    // Create local task for this job.
    /** @var \Drupal\tmgmt_local\LocalTaskInterface $local_task */
    $local_task = LocalTask::create(array(
      'uid' => $job->getOwnerId(),
      'tuid' => $tuid,
      'tjid' => $job->id(),
      'title' => $job->label(),
    ));
    // If we have translator then switch to pending state.
    if ($tuid) {
      $local_task->status = LocalTaskInterface::STATUS_PENDING;
    }
    $local_task->save();

    // Create task items.
    foreach ($job_items as $item) {
      $local_task->addTaskItem($item);
      $item->active();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    $languages = tmgmt_local_supported_target_languages($source_language);
    if (\Drupal::config('tmgmt_local.settings')->get('allow_all')) {
      $languages += parent::getSupportedTargetLanguages($translator, $source_language);
    }
    return $languages;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedLanguagePairs(TranslatorInterface $translator) {

    if (!empty($this->language_pairs)) {
      return $this->language_pairs;
    }

    $roles = user_roles(TRUE, 'provide translation services');

    $query = \Drupal::database()->select('field_data_tmgmt_translation_skills', 'ts');

    $query->join('users', 'u', 'u.uid = ts.entity_id AND u.status = 1');

    $query->addField('ts', 'tmgmt_translation_skills_language_from', 'source_language');
    $query->addField('ts', 'tmgmt_translation_skills_language_to', 'target_language');

    $query->condition('ts.deleted', 0);
    $query->condition('ts.entity_type', 'user');

    if (!in_array(AccountInterface::AUTHENTICATED_ROLE, array_keys($roles))) {
      $query->join('users_roles', 'ur', 'ur.uid = u.uid AND ur.rid');
      $or_conditions = (new Condition('OR'))->condition('ur.rid', array_keys($roles), 'IN')
        ->condition('u.uid', 1);
      $query->condition($or_conditions);
    }

    foreach ($query->execute()->fetchAll() as $item) {
      $this->language_pairs[] = array(
        'source_language' => $item->source_language,
        'target_language' => $item->target_language,
      );
    }

    return $this->language_pairs;
  }

}
