<?php

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\sfgov_utilities\Utility;

try {
  $deptNodes = Utility::getNodes('department');

  foreach($deptNodes as $dept) {
    $deptId = $dept->id();

    // get part of values
    $partOf = $dept->get('field_parent_department')->getValue();

    if (!empty($partOf)) {
      $partOfRefId = $partOf[0]['target_id'];
      $langcode = $dept->get('langcode')->value;

      // load tagged parent
      $parentDept = Node::load($partOfRefId);

      if ($parentDept->hasTranslation($langcode)) { // check translation
        $parentDeptTranslation = $parentDept->getTranslation($langcode);
        $parentDivisions = $parentDeptTranslation->get('field_divisions')->getValue();

        // check that this dept isn't already added as a division on the parent dept
        $found = false;
        foreach ($parentDivisions as $parentDivision) {
          if ($deptId == $parentDivision["target_id"]) {
            $found = true;
            break;
          }
        }

        if ($found == false) {
          $parentDivisions[] = [
            'target_id' => $deptId
          ];
          $parentDeptTranslation->set('field_divisions', $parentDivisions);
          $parentDeptTranslation->save();
        }
      }
    }
  }
} catch (\Exception $e) {
  error_log($e->getMessage());
}
