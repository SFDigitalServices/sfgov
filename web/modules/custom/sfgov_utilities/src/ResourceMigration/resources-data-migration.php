<?php

use Drupal\sfgov_utilities\ResourceMigration\ResourceMigration;

$rm = new ResourceMigration();
$rm->setDryRun(false);

$rm->migrateAboutAndPublicBodyResources();
$rm->migrateCampaignResources();
$rm->migrateTopicsAndDepartments();
$rm->migrateResourceCollections();

echo "\/***** verify migration report *****\/\n\n";
$rm->verifyMigration();
echo "\n\n";

echo "\/***** node report *****\/\n\n";
$rm->getNodeReport();
echo "\n\n";

echo "\/***** duplicates report *****\/\n\n";
$rm->getDuplicateReport();
echo "\n\n";
