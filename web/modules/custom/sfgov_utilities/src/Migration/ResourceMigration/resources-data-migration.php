<?php

use Drupal\sfgov_utilities\Migration\ResourceMigration\ResourceMigration;

$rm = new ResourceMigration();
$rm->setDryRun(false);

$rm->migrateAboutAndPublicBodyResources();
// $rm->migrateCampaignResources();
// $rm->migrateTopicsAndDepartments();
// $rm->migrateResourceCollections();

// $rm->migrateTopicsAndDepartmentsResourceSubheading();

// echo "\/***** verify migration report *****\/\n\n";
// $rm->verifyMigration();
// echo "\n\n";

echo "\/***** node report *****\/\n\n";
$rm->getNodeReport();
echo "\n\n";

echo "\/***** report *****\/\n\n";
echo json_encode($rm->getReport(), JSON_UNESCAPED_SLASHES);
echo "\n\n";

// echo "\/***** duplicates report *****\/\n\n";
// $rm->getDuplicateReport();
// echo "\n\n";
