<?php

use Drupal\node\Entity\Node;
use Drupal\sfgov_utilities\Utility;

try {
  $deptNodes = Utility::getNodes('department');
  foreach($deptNodes as $dept) {
    $dept->field_include_in_list->value = TRUE;
    echo "Updated " . $dept->getTitle() . " (" . $dept->id() . ")\n";
    $dept->save();
  }
} catch(\Exception $e) {
  error_log($e->getMessage(), "\n");
}