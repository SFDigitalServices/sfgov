<?php

use Drupal\sfgov_utilities\Migration\FieldDepartmentMigration\FieldDepartmentMigration;

$deptsMigration = new FieldDepartmentMigration();
$deptsMigration->migrateToFieldDepartments();

echo json_encode($deptMigration->getReport(), JSON_UNESCAPED_SLASHES);
