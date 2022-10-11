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
      $publicBodies = $dept->get('field_public_bodies')->getValue();

      // migrate divisions
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

      // migrate public bodies
      if (!empty($publicBodies)) {
        // check if url is internal or external
        // if external, it cannot be added as a related agencies item
        
        // iterate through public body links
        foreach ($publicBodies as $publicBody) {
          $link = Paragraph::load($publicBody['target_id']);
          $uri = $link->get('field_link')->getValue()[0]['uri'];
          
          // error_log($dept->getTitle() . " (" . $dept->id() . "): " . $uri);
          // error_log(\Drupal\Component\Utility\UrlHelper::isExternal($uri));
          // error_log(\Drupal::service('path_alias.manager')->getAliasByPath($uri));

          if (strpos($uri, 'entity')) { // internal url
            
          } elseif (strpos($uri, 'https://sf.gov')) { // absolute sf.gov url

          } else { // other urls, report

          }
        }
      }

      $dept->save();
    }
  }
} catch (\Exception $e) {
  error_log($e->getMessage());
}
