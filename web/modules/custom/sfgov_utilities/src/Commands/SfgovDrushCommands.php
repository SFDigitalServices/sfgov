<?php

namespace Drupal\sfgov_utilities\Commands;

use Drush\Commands\DrushCommands;use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;

/**
 * A drush command file for custom commands.
 *
 * @package Drupal\sfgov_utilities\Commands
 */
class SfgovDrushCommands extends DrushCommands {

  /**
   * Drush command that deletes completed/aborted tmgmt job items from xtm.
   *
   * @command SfgovDrushCommands:tmgmt_clean_xtm
   * @aliases tmgmt-clean-xtm
   */
  public function tmgmt_clean_xtm() {
    // Find all tmgmt jobs from the xtm provider that are either "aborted" (4)
    // or "complete" (5).
    $ids = \Drupal::entityQuery('tmgmt_job')
      ->condition('state', [4, 5], 'IN')
      ->condition('translator', ['xtm', 'xtm_test'], 'IN')
      ->execute();
    if (!empty($ids)) {
      $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_job');
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        // Double check that the entity is complete or aborted, and delete.
        if ($entity->isFinished() || $entity->isAborted()) {
          // Give the entity a valid translator so that it can be deleted.
          // The tmgmt_contentapi_tmgmt_job_delete hook in
          // tmgmt_contentapi.module requires a job to have a translator before
          // deleting.
          $entity->translator->target_id = 'contentapi';
          $entity->delete();
        }
      }
    }
  }

  /**
   * Drush command that deletes completed tmgmt job items .
   *
   * @command SfgovDrushCommands:tmgmt_clean_completed
   * @aliases tmgmt-clean-completed
   */
  public function tmgmt_clean_completed() {
    $ids = \Drupal::entityQuery('tmgmt_job')
      ->condition('state', '5')
      ->execute();
    if (!empty($ids)) {
      $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_job');
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        $entity->delete();
      }
    }
  }

  /**
   * Drush command that deletes aborted tmgmt job items .
   *
   * @command SfgovDrushCommands:tmgmt_clean_aborted
   * @aliases tmgmt-clean-aborted
   */
  public function tmgmt_clean_aborted() {
    $ids = \Drupal::entityQuery('tmgmt_job')
      ->condition('state', '4')
      ->execute();
    if (!empty($ids)) {
      $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_job');
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        $entity->delete();
      }
    }
  }

}
