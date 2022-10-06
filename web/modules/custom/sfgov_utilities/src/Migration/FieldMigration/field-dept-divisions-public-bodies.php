<?php

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\sfgov_utilities\Utility;


try {
  $deptNodes = Utility::getNodes('department');
  foreach($deptNodes as $dept) {
    if ($dept->id() == 6602) {
      // migrate divisions
      $divisions = $dept->get('field_divisions')->getValue();

      if (!empty($divisions)) {
        // create agency section paragraph with divisions values
        $agencySection = Paragraph::create([
          "type" => "agency_section",
          "field_section_title_list" => "Divisions",
          "field_nodes" => $divisions,
        ]);

        // append new agency section to divisions and subcommittees field (field_paragraphs)
        $agencySections = $dept->get('field_agency_sections')->getValue();
        $agencySections[] = $agencySection;
        $dept->set('field_agency_sections', $agencySections);

        // remove old field values
        $dept->set('field_divisions', []);
      }

      $dept->save();
    }
  }
} catch (\Exception $e) {
  error_log($e->getMessage(), "\n");
}
