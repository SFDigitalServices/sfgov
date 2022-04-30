<?php

use Drupal\sfgov_utilities\Migration\FieldDepartmentMigration\FieldDepartmentMigration;

$deptsMigration = new FieldDepartmentMigration();
$deptsMigration->migrateToFieldDepartments();

echo json_encode($deptsMigration->getReport(), JSON_UNESCAPED_SLASHES);
