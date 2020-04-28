<?php

namespace Drupal\tmgmt;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\tmgmt\Events\ShouldCreateJobEvent;
use Drupal\tmgmt\Entity\Job;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\tmgmt\Events\ContinuousEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A service manager for continuous jobs.
 */
class ContinuousManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The source plugin manager.
   *
   * @var \Drupal\tmgmt\SourceManager
   */
  protected $sourcePluginManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The translation manager.
   *
   * @var \Drupal\tmgmt\TranslatorManager
   */
  protected $translatorManager;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new ContinuousManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\tmgmt\SourceManager $source_plugin_manager
   *   The source plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\tmgmt\TranslatorManager $translator_manager
   *   The translation manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SourceManager $source_plugin_manager, ConfigFactoryInterface $config_factory, TranslatorManager $translator_manager, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->sourcePluginManager = $source_plugin_manager;
    $this->configFactory = $config_factory;
    $this->translatorManager = $translator_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Checks if there is any continuous job.
   *
   * @return bool
   *   Returns TRUE if there is continuous job, FALSE otherwise.
   */
  public function hasContinuousJobs() {
    $id = $this->entityTypeManager->getStorage('tmgmt_job')->getQuery()
      ->condition('job_type', Job::TYPE_CONTINUOUS)
      ->range(0, 1)
      ->execute();
    return !empty($id);
  }

  /**
   * Returns all continuous jobs with the given language.
   *
   * @param string $source_langcode
   *   Source language.
   *
   * @return array
   *   Array of continuous jobs.
   */
  public function getContinuousJobs($source_langcode) {
    $jobs = array();
    $ids = $this->entityTypeManager->getStorage('tmgmt_job')->getQuery()
      ->condition('source_language', $source_langcode)
      ->condition('job_type', Job::TYPE_CONTINUOUS)
      ->condition('state', Job::STATE_CONTINUOUS)
      ->execute();
    if (!empty($ids)) {
      $jobs = Job::loadMultiple($ids);
    }
    return $jobs;
  }

  /**
   * Creates job item and submits according to the configured settings.
   *
   * The job item will only be created if the given source plugin for the job is
   * configured to accept this source.
   *
   * The job item will be immediately submitted to the translator unless
   * this happens on cron runs.
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
   * @return \Drupal\tmgmt\Entity\JobItem
   *   Continuous job item.
   *
   * @see \Drupal\tmgmt\Events\ContinuousEvents::SHOULD_CREATE_JOB
   */
  public function addItem(Job $job, $plugin, $item_type, $item_id) {
    // Check if a job item should be created.
    $most_recent_job_item = $job->getMostRecentItem($plugin, $item_type, $item_id);
    $should_create_item = $this->sourcePluginManager->createInstance($plugin)->shouldCreateContinuousItem($job, $plugin, $item_type, $item_id);

    // Some modules might want to filter out candidates for items.
    $event = new ShouldCreateJobEvent($job, $plugin, $item_type, $item_id, $should_create_item);
    $this->eventDispatcher->dispatch(ContinuousEvents::SHOULD_CREATE_JOB, $event);

    if ($event->shouldCreateItem()) {
      if ($most_recent_job_item) {
        // If the most recent job item is active do nothing.
        if (!$most_recent_job_item->isAborted() && !$most_recent_job_item->isAccepted()) {
          $most_recent_job_item->addMessage('Source was updated, changes were ignored as job item is still active.');
          return NULL;
        }
      }
      // If there are no job items or it's finished/aborted create new one.
      $job_item = $job->addItem($plugin, $item_type, $item_id);
      $job_item->addMessage('Continuous job item created');

      // Only submit the item if cron submission is disabled.
      if (!$this->configFactory->get('tmgmt.settings')->get('submit_job_item_on_cron')) {
        $translator = $job->getTranslatorPlugin();
        \Drupal::moduleHandler()->invokeAll('tmgmt_job_before_request_translation', [[$job_item]]);
        if ($job_item->getCountPending() > 0) {
          $translator->requestJobItemsTranslation([$job_item]);
        }
        \Drupal::moduleHandler()->invokeAll('tmgmt_job_after_request_translation', [[$job_item]]);
      }
      return $job_item;
    }
    return NULL;
  }

  /**
   * Returns TRUE if there are translators that support continuous jobs.
   */
  public function checkIfContinuousTranslatorAvailable() {
    $translator_plugins = $this->translatorManager->getDefinitions();
    foreach ($translator_plugins as $type => $definition) {
      $translator_type = $this->translatorManager->createInstance($type);
      if ($translator_type instanceof ContinuousTranslatorInterface) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
