<?php

use Drupal\sfgov_utilities\Migration\FieldDeptMigration\FieldDeptMigration;

$deptMigration = new FieldDeptMigration();
$deptMigration->migrateToFieldDept();

echo json_encode($deptMigration->getReport(), JSON_UNESCAPED_SLASHES);
