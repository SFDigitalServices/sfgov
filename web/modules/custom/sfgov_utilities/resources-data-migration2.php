<?php

use Drupal\sfgov_utilities\ResourceMigration\ResourceMigration;

$rm = new ResourceMigration();

$rm->migrateAboutResources();
$rm->migrateCampaignResources();
$rm->migrateTopicsAndDepartments();

echo "\/***** node report *****\/";
$rm->getNodeReport();

echo "\/***** duplicates report *****\/";
$rm->getDuplicateReport();
