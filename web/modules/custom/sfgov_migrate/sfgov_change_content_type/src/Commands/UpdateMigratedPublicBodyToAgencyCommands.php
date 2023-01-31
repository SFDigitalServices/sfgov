<?php

namespace Drupal\sfgov_change_content_type\Commands;

use Drush\Commands\DrushCommands;

/**
 * a drush command file to update migrated public_body -> agency nodes.
 *
 * "drush sfgov-update-fields" to update reference fields.
 *
 * "drush sfgov-publish-migrated-agencies" to publish all the new agencies.
 * - Currently not working
 *
 * See google sheet for field info.
 * - https://docs.google.com/spreadsheets/d/13e2eGiuXLqrOrM1YPOaXYOP-sOfvJUVutQNgqEfIuzU/edit#gid=222704937
 *
 * @package Drupal\sfgov_change_content_type\Commands
 */
class UpdateMigratedPublicBodyToAgencyCommands extends DrushCommands {

  /**
   * Helper function to return the migration map data.
   *
   * @return mixed
   */
  public function query_migration_table() {
    $database = \Drupal::service('database');
    $query = $database->select('migrate_map_convert_public_body_to_agency', 'm');
    return $query
      ->condition('m.source_row_status', 0)
      ->fields('m', ['sourceid1', 'sourceid2', 'destid1'])
      ->orderBy('destid1', 'ASC')
      ->execute();
  }

  /**
   * Return an array of ids for old public bodies and the created agencies.
   *
   * @return array
   */
  public function get_migration_mappings(): array {
    $items = [];
    foreach ($this->query_migration_table() as $record) {
      $derp = true;
      $items[] = [
        'old_public_body_nid' => $record->sourceid1,
        'new_agency_nid' => $record->destid1,
      ];
    }
    return $items;
  }

  /**
   * Update field values for reference fields that may have old public body ids.
   *
   * @command sfgov-change-content-type:update-fields
   * @aliases sfgov-update-fields
   * @usage sfgov-change-content-type:update-fields
   *   Display 'stuff'.
   */
  public function update_fields() {
    $migration_mapping = $this->get_migration_mappings();
    $database = \Drupal::service('database');
    $replaced_items = [];

    // Database table names to process and the column names that will be edited.
    // table_name => column_name.
    $fields = [
      'resource__field_department' => 'field_department_target_id',
      'location__field_department' => 'field_department_target_id',
      'user__field_departments' => 'field_departments_target_id',
      'node__field_parent_department' => 'field_parent_department_target_id',
      'node_revision__field_parent_department' => 'field_parent_department_target_id',
      'node__field_departments' => 'field_departments_target_id',
      'node_revision__field_departments' => 'field_departments_target_id',
      'node__field_city_department' => 'field_city_department_target_id',
      'node_revision__field_city_department' => 'field_city_department_target_id',
      'node__field_dept' => 'field_dept_target_id',
      'node_revision__field_dept' => 'field_dept_target_id',
      'node__field_public_body' => 'field_public_body_target_id',
      'node_revision__field_public_body' => 'field_public_body_target_id',
      'paragraph__field_node' => 'field_node_target_id',
      'paragraph_revision__field_node' => 'field_node_target_id',
      'paragraph__field_department' => 'field_department_target_id',
      'paragraph_revision__field_department' => 'field_department_target_id',
      'paragraph__field_agency_reference' => 'field_agency_reference_target_id',
      'paragraph_revision__field_agency_reference' => 'field_agency_reference_target_id',
    ];

    // This make a direct database update and changes the value on the field.
    // Not sure yet if this should be changed to more of a node::load() and save
    // process.
    foreach ($fields as $field_name => $column_name) {
      foreach ($migration_mapping as $map) {
        $updated = $database->update($field_name)
          ->fields([$column_name => $map['new_agency_nid']])
          ->condition($column_name, $map['old_public_body_nid'], '=')
          ->execute();
        if ($updated) {
          $replaced_items[] = $map['old_public_body_nid'] . ' was replaced with ' . $map['new_agency_nid'] . ' for ' . $field_name;
        }
      }
    }

    if (count($replaced_items) > 0) {
      $this->output()->writeln(count($replaced_items) . ' reference values had their public body replaced with a new agency node.');

      // Clear caches if changes were applied.
      drupal_flush_all_caches();
      $this->output->writeln('Caches cleared.');
    }
    else {
      $this->output->writeln('No reference values were updated');
    }
  }

  /**
   * Publish (via status and moderation states), all new agencies.
   *
   * @NOTE: Not sure if this is best way to publish all that are needed. This
   * could also be done in the content admin UI. The UI would give more control
   * over what gets published and would prevent a scenario where stuff that
   * shouldn't be published is published by mistake.
   *
   * @command sfgov-change-content-type:publish-migrated-agencies
   * @aliases sfgov-publish-migrated-agencies
   * @usage sfgov-change-content-type:publish-migrated-agencies
   *   Display 'stuff'.
   */
  public function publish_migrated_agencies() {
    $migration_mapping = $this->get_migration_mappings();
    $storage_handler = \Drupal::entityTypeManager()->getStorage('node');
    $published_items = [];
    $moderated_items = [];

    foreach ($migration_mapping as $map_pair) {
      $node = $storage_handler->load($map_pair['new_agency_nid']);
      if ($node->get('moderation_state')->value != 'published' || $node->get('status')->value != 1) {
        $node->set('moderation_state', 'published');
        $moderated_items[] = 'node:' . $map_pair['new_agency_nid'] . ' was moderated to published';
        $node->setPublished();
        $published_items[] = 'node:' . $map_pair['new_agency_nid'] . ' was set to published status';
        $node->save();
      }
    }

    if (count($published_items) > 0) {
      $this->output()->writeln(count($published_items) . ' agencies were published.');
    }
    else {
      $this->output->writeln('No agencies were published');
    }

    if (count($moderated_items) > 0) {
      $this->output()->writeln(count($moderated_items) . ' agencies were moderated to published state.');
    }
    else {
      $this->output->writeln('No agencies were moderated');
    }

    // Clear caches if changes were applied
    if (count($published_items) > 0 || count($moderated_items) > 0) {
      drupal_flush_all_caches();
      $this->output->writeln('Caches cleared.');
    }
  }

  /**
   * Unpublishes all public_bodies.
   *
   * @command sfgov-change-content-type:unpub-public-bodies
   * @aliases sfgov-unpub-public-bodies
   * @usage sfgov-change-content-type:unpub-public-bodies
   *   Display 'stuff'.
   */
  public function unpub_public_bodies() {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'public_body')
      ->execute();
    $storage_handler = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $storage_handler->loadMultiple($nids);
    foreach ($nodes as $node) {
      $node->set('moderation_state', 'archived');
      $node->setUnpublished();
      $node->save();
    }
  }

  /**
   * Echos back the migration map results. (Helper function to show mapping.)
   *
   * @command sfgov-change-content-type:echo-mapping-info
   * @aliases sfgov-echo-mapping-info
   * @usage sfgov-change-content-type:echo-mapping-info
   *   Display 'stuff'.
   */
  public function echo_mapping() {
    $results = $this->query_migration_table();
    $items = [];
    foreach ($results as $record) {
      $items[] = $record->sourceid1;
      $this->output()->writeln('Public body node/' . $record->sourceid1 . ' with vid:' . $record->sourceid2 . ' goes to new Agency node/' . $record->destid1);
    }
    $this->output()->writeln('Total count:' . count($items));
  }

  /**
   * Echos back the subcommittees that need to be updated.
   *
   * @command sfgov-change-content-type:echo-subcommittee-list
   * @aliases sfgov-echo-subcommittee-list
   * @usage sfgov-change-content-type:echo-subcommittee-list
   *   Display 'stuff'.
   */
  public function echo_subcommittee_list() {
    $migration_mapping = [];
    foreach ($this->query_migration_table() as $record) {
      $migration_mapping[$record->sourceid1] = $record->destid1;
    }

    $database = \Drupal::service('database');
    $query = $database->select('node__field_subcommittees', 's')
      ->fields('s', ['entity_id', 'field_subcommittees_target_id'])
      ->orderBy('entity_id', 'ASC');
    $results = $query->execute()->fetchAll();
    $storage_handler = \Drupal::entityTypeManager()->getStorage('node');

    foreach ($results as $result) {
      $agency = $storage_handler->load($migration_mapping[$result->entity_id]);
      $subcommittee = $storage_handler->load($migration_mapping[$result->field_subcommittees_target_id]);
      $agency_name = $agency->getTitle();
      $agency_id = $agency->id();
      $subcommittee_name = $subcommittee->getTitle();
      $subcommittee_id = $subcommittee->id();
      $this->output()->writeln("The agency {$agency_name} (id: {$agency_id}) should have subcommittee {$subcommittee_name} (id: {$subcommittee_id})");
    }
  }

}
