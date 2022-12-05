<?php

use Drupal\node\Entity\Node;
use Drupal\sfgov_utilities\Utility;

try {
  $deptNodes = Utility::getNodes('department');
  foreach($deptNodes as $dept) {
    $currentSite = $dept->field_url->uri;
    $goToSite = $dept->field_go_to_current_url->value;
    echo $dept->getTitle() . " (" . $dept->id() . ") \n" . 
      "\tcurrent site url: $currentSite\n" .
      "\tgo to site: $goToSite" .
      "\n";

    if ($goToSite == true) {
      $dept->set('field_direct_external_url', [
        'uri' => $currentSite
      ]);
      $dept->save();
      echo "saved " . $dept->getTitle() . " <---\n";
    }
  }
} catch(\Exception $e) {
  error_log($e->getMessage(), "\n");
}
