<?php

use Drupal\sfgov_utilities\Utility;
use Drupal\sfgov_utilities\Migration\FieldMigration\TopLevelFieldMigration;

try {
  // migrate to field_departments from field_dept or field_public_body
  $informationPageNodes = Utility::getNodes('information_page');
  $campaignNodes = Utility::getNodes('campaign');
  $deptTableNodes = Utility::getNodes('department_table');
  $eventNodes = Utility::getNodes('event');
  $formConfirmPageNodes = Utility::getNodes('form_confirmation_page');
  $meetingNodes = Utility::getNodes('meeting');
  $newsNodes = Utility::getNodes('news');
  $resourceCollectionNodes = Utility::getNodes('resource_collection');
  $stepByStepNodes = Utility::getNodes('step_by_step');

  $fieldMigration = new TopLevelFieldMigration();
  $fieldMigration->migrate($informationPageNodes, 'field_public_body', 'field_departments');
  $fieldMigration->migrate($campaignNodes, 'field_dept', 'field_departments');
  $fieldMigration->migrate($deptTableNodes, 'field_dept', 'field_departments');
  $fieldMigration->migrate($eventNodes, 'field_dept', 'field_departments');
  $fieldMigration->migrate($formConfirmPageNodes, 'field_dept', 'field_departments');
  $fieldMigration->migrate($meetingNodes, 'field_dept', 'field_departments');
  $fieldMigration->migrate($newsNodes, 'field_dept', 'field_departments');
  $fieldMigration->migrate($resourceCollectionNodes, 'field_dept', 'field_departments');
  $fieldMigration->migrate($stepByStepNodes, 'field_dept', 'field_departments');

  echo json_encode($fieldMigration->getReport(), JSON_UNESCAPED_SLASHES) . "\n";
} catch(\Exception $e) {
  echo $e->getMessage(), "\n";
}
