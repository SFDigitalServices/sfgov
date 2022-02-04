<?php

use Drupal\sfgov_utilities\ResourceMigration\ResourceMigration;

$rm = new ResourceMigration();
// $rm->migrateCampaignResources();
// $rm->getReport(TRUE);

// $rm->migrateResourceCollectionResources();

$rm->migrateResources(TRUE); // report only
// $rm->migrateResources();
// $rm->getDuplicateReport();
$rm->getNodeReport();

