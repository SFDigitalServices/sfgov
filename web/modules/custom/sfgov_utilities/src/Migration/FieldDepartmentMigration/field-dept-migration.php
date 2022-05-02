<?php

use Drupal\sfgov_utilities\Utility;
use Drupal\sfgov_utilities\Migration\FieldDepartmentMigration\FieldDepartmentMigration;

$informationPageNodes = Utility::getNodes('information_page');
$campaignNodes = Utility::getNodes('campaign');
$deptTableNodes = Utility::getNodes('department_table');
$eventNodes = Utility::getNodes('event');
$formConfirmPageNodes = Utility::getNodes('form_confirmation_page');
$meetingNodes = Utility::getNodes('meeting');
$newsNodes = Utility::getNodes('news');
$resourceCollectionNodes = Utility::getNodes('resource_collection');
$stepByStepNodes = Utility::getNodes('step_by_step');

$deptsMigration = new FieldDepartmentMigration();
$deptsMigration->migrateToFieldDepartments($informationPageNodes, 'field_public_body');
$deptsMigration->migrateToFieldDepartments($campaignNodes, 'field_dept');
$deptsMigration->migrateToFieldDepartments($deptTableNodes, 'field_dept');
$deptsMigration->migrateToFieldDepartments($eventNodes, 'field_dept');
$deptsMigration->migrateToFieldDepartments($formConfirmPageNodes, 'field_dept');
$deptsMigration->migrateToFieldDepartments($meetingNodes, 'field_dept');
$deptsMigration->migrateToFieldDepartments($resourceCollectionNodes, 'field_dept');
$deptsMigration->migrateToFieldDepartments($stepByStepNodes, 'field_dept');

echo json_encode($deptsMigration->getReport(), JSON_UNESCAPED_SLASHES);
