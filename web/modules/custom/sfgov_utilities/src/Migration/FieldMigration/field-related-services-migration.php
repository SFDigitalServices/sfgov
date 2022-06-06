<?php

use Drupal\sfgov_utilities\Utility;
use Drupal\sfgov_utilities\Migration\FieldMigration\TopLevelFieldMigration;

try {
  $transactionNodes = Utility::getNodes('transaction');
  $relatedServicesFieldMigration = new TopLevelFieldMigration();
  $relatedServicesFieldMigration->migrate($transactionNodes, 'field_transactions', 'field_related_content');
} catch(\Exception $e) {
  error_log($e->getMessage(), "\n");
}
