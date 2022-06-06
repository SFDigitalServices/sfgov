<?php

use Drupal\sfgov_utilities\Utility;
use Drupal\sfgov_utilities\Migration\FieldMigration\TopLevelFieldMigration;

try {
  $transactionNodes = Utility::getNodes('transaction');
  $relatedServicesFieldMigration = new TopLevelFieldMigration();
  $relatedServicesFieldMigration->migrate($transactionNodes, 'field_transactions', 'field_related_content');

  echo json_encode($relatedServicesFieldMigration->getReport(), JSON_UNESCAPED_SLASHES) . "\n";
} catch(\Exception $e) {
  error_log($e->getMessage(), "\n");
}
