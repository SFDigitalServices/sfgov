<?php

namespace Drupal\sfgov_utilities\ResourceMigration;

class ResourceMigrationReport {

  // report structure
  // nid, content_type, title

  private $report;

  public function __construct() {
    $this->report = [];
  }

  public function addItem($item, $key = NULL) {
    // echo "$key\n";
    if($key) {
      $this->report[$key][] = $item;
    } else {
      $this->report[] = $item;
    }
  }

  public function getReport(bool $json = FALSE) {
    echo "json: $json\n";
    if($json) {
      echo "print json\n";
      echo json_encode($this->report);
    } else {
      echo "print array\n";
      print_r($this->report);
    }
  }
}