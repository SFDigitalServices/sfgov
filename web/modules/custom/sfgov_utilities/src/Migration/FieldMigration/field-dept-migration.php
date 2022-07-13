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
  unset($informationPageNodes);

  $fieldMigration->migrate($campaignNodes, 'field_dept', 'field_departments');
  unset($campaignNodes);

  $fieldMigration->migrate($deptTableNodes, 'field_dept', 'field_departments');
  unset($deptTableNodes);

  $fieldMigration->migrate($eventNodes, 'field_dept', 'field_departments');
  unset($eventNodes);

  $fieldMigration->migrate($formConfirmPageNodes, 'field_dept', 'field_departments');
  unset($formConfirmPageNodes);

  $fieldMigration->migrate($meetingNodes, 'field_dept', 'field_departments');
  unset($meetingNodes);

  $fieldMigration->migrate($newsNodes, 'field_dept', 'field_departments');
  unset($newsNodes);

  $fieldMigration->migrate($resourceCollectionNodes, 'field_dept', 'field_departments');
  unset($resourceCollectionNodes);

  $fieldMigration->migrate($stepByStepNodes, 'field_dept', 'field_departments');
  unset($stepByStepNodes);

  echo json_encode($fieldMigration->getReport(), JSON_UNESCAPED_SLASHES) . "\n";
} catch(\Exception $e) {
  echo $e->getMessage(), "\n";
}
