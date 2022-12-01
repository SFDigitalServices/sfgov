<?php

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\sfgov_utilities\Utility;

try {
  $deptNodes = Utility::getNodes('department');

  foreach($deptNodes as $dept) {
    $deptId = $dept->id();

    if ($deptId == 6878) {
      // get part of values
      $partOf = $dept->get('field_parent_department')->getValue();

      if (!empty($partOf)) {
        echo "part of:\n";
        echo "dept: " . $dept->getTitle() . ": " . "\n";
        print_r($partOf);

        $partOfRefId = $partOf[0]['target_id'];
        $langcode = $dept->get('langcode')->value;

        $parentDept = Node::load($partOfRefId);
        if ($parentDept->hasTranslation($langcode)) {
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
            echo "not found\n";
            echo "add " . $dept->getTitle() . "($deptId) to " . $parentDeptTranslation->getTitle() . "($partOfRefId) for language " . $dept->get('langcode')->value . "\n";
            
            $parentDivisions[] = [
              'target_id' => $deptId
            ];
            $parentDeptTranslation->set('field_divisions', $parentDivisions);
            $parentDeptTranslation->save();
          }
        } else {
          echo "skipping translation: $langcode\n";
        }
      }
    }
  }
} catch (\Exception $e) {
  error_log($e->getMessage());
}
