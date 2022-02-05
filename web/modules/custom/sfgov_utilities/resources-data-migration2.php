<?php

use Drupal\sfgov_utilities\ResourceMigration\ResourceMigration;

$rm = new ResourceMigration();
// $rm->migrateCampaignResources();
// $rm->getReport(TRUE);

// $rm->migrateResourceCollectionResources();

// $rm->migrateResources(TRUE); // report only

// $rm->getDuplicateReport();



$rm->migrateAboutResources();
$rm->migrateTopicsAndDepartments();

echo "\/***** node report *****\/";
$rm->getNodeReport();

echo "\/***** duplicates report *****\/";
$rm->getDuplicateReport();
