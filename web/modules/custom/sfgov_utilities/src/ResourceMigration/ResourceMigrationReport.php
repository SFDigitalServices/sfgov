<?php

namespace Drupal\sfgov_utilities\ResourceMigration;

class ResourceMigrationReport {

  private $report;

  public function __construct() {
    $this->report = [];
  }

  public function addItem($item, $key = NULL) {
    if($key) {
      $this->report[$key][] = $item;
    } else {
      $this->report[] = $item;
    }
  }

  public function getReport(bool $json = FALSE) {
    if($json) {
      echo json_encode($this->report, JSON_UNESCAPED_SLASHES);
    } else {
      print_r($this->report);
    }
  }
}