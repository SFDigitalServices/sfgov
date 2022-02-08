<?php

use Drupal\sfgov_utilities\ResourceMigration\ResourceMigration;

$rm = new ResourceMigration();
$rm->setDryRun(false);

$rm->migrateAboutResources();
// $rm->migrateCampaignResources();
// $rm->migrateTopicsAndDepartments();
// $rm->migrateResourceCollections();

echo "\/***** node report *****\/\n\n";
$rm->getNodeReport();
echo "\n\n";

echo "\/***** validation report *****\/\n\n";
$rm->getValidationReport();
echo "\n\n";

echo "\/***** duplicates report *****\/\n\n";
$rm->getDuplicateReport();
echo "\n\n";
