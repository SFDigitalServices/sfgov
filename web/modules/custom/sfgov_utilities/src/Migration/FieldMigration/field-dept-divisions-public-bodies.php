<?php

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\sfgov_utilities\Utility;


try {
  $deptNodes = Utility::getNodes('department');
  $externalUrls = [];
  
  foreach($deptNodes as $dept) {
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
      $relatedAgencies = [];
      // check if url is internal or external
      // if external, it cannot be added as a related agencies item

      // iterate through public body links
      foreach ($publicBodies as $publicBody) {
        $link = Paragraph::load($publicBody['target_id']);
        $linkValue = $link->get('field_link')->getValue();
        $uri = $linkValue[0]['uri'];
        $text = $linkValue[0]['title'];
        $drupalPath = "";

        if (strpos($uri, 'entity') !== false || strpos($uri, 'https://sf.gov') !== false) { // internal url
          $drupalPath = \Drupal::service('path_alias.manager')->getPathByAlias(parse_url($uri, PHP_URL_PATH));
          $refNid = substr($drupalPath, strrpos($drupalPath, '/') + 1);
          if (!empty($refNid)) {
            $relatedAgencies[] = [
              "target_id" => $refNid
            ];
          }
        } else { // other urls, report
          $externalUrls[] = [
            "nid" => $dept->id(),
            "public_body_url_text" => $text,
            "public_body_url" => $uri,
          ];
        }
      }

      echo "related agencies\n";
      print_r($relatedAgencies);
      echo "\n\n";

      $dept->set('field_departments', $relatedAgencies);
      $dept->set('field_public_bodies', []);
    }

    $dept->save();
  }

  echo "external urls\n";
  print_r($externalUrls);
} catch (\Exception $e) {
  error_log($e->getMessage());
}
