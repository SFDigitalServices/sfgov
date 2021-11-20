<?php

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use \Drupal\media\entity\Media;

/**
 * Create media entities for existing profile field_photo_images and assign to new field_profile_photo media entity reference
 */
function sfgov_utilities_deploy_00_profile_photos() {
  $nids = \Drupal::entityQuery('node')->condition('type','person')->execute();
  $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

  $mediaIds = \Drupal::entityQuery('media')->condition('bundle', 'image')->execute();
  $mediaImages = Media::loadMultiple($mediaIds);
  $mediaFileNames = [];

  foreach($mediaImages as $mediaImage) {
    $mediaFileNames[] = trim($mediaImage->getName());
  }

  foreach($nodes as $node) {
    $title = $node->getTitle();
    $field_photo_entity = $node->field_photo->entity;

    if (!empty($field_photo_entity)) {
      $field_photo_uri = $field_photo_entity->getFileUri();
      $field_photo_filename = trim(substr($field_photo_uri, strrpos($field_photo_uri, "/")+1));
      $field_photo_id = $field_photo_entity->id();
  
      echo $title . " (" . $field_photo_id . "):" . "[" . $field_photo_filename . "] ";
  
      if (array_search($field_photo_filename, $mediaFileNames) == false) {
        echo "...no media found, create and assign";
        $media_image = Media::create([
          'bundle' => 'image',
          'uid' => \Drupal::currentUser()->id(),
          'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
          'field_image' => [
            'target_id' => $field_photo_id,
            'alt' => t($title),
            'title' => t($title),
          ],
          'field_media_image' => [
            "target_id" => $field_photo_id,
            'alt' => t($title),
            'title' => t($title),
          ]
        ]);
        $media_image->setPublished(true);
        $media_image->save();
  
        $node->field_profile_photo->target_id = $media_image->id();
        $node->get('field_photo')->removeItem(0);
        $node->save();
  
      } else {
        echo "...media found, skip";
      }
  
      echo "\n";
    }
  }
}

/**
 * Move existing people paragraphs to profile group paragraphs for the front page
 */
function sfgov_utilities_deploy_01_homepage_profile_group() {
  $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail'=>'webmaster@sfgov.org']);
  $user = reset($users);
  $user_id = $user->id();

  // there's only one landing page
  $landingPage = Node::load('2');

  // get content sections
  $contentFieldSections = $landingPage->get('field_content')->getValue();

  // collect the people section indexes for removal
  $peopleSectionRemoveIds = [];

  foreach($contentFieldSections as $contentFieldSection) {
    $sectionParagraph = Paragraph::load($contentFieldSection['target_id']);

    if(!empty($sectionParagraph)) {
      $sectionTitle = $sectionParagraph->get('field_title')->value;
      // look for the elected officials section
      if(strToLower($sectionTitle) == 'elected officials') {
        // get all the people sections
        $peopleSections = $sectionParagraph->get('field_content');
        $peopleSectionsValue = $sectionParagraph->get('field_content')->getValue();
        for($i=0; $i < count($peopleSectionsValue); $i++) {
          $peopleSection = $peopleSectionsValue[$i];
          $peopleSectionParagraph = Paragraph::load($peopleSection['target_id']);
          if($peopleSectionParagraph->getType() == 'people') {
            // we will remove this later, so track the id
            $peopleSectionRemoveIds[] = $peopleSectionParagraph->id();
            // capture the people section data fields
            $peopleSectionTitle = $peopleSectionParagraph->get('field_people_title')->value;
            $peopleSectionDescription = $peopleSectionParagraph->get('field_description')->value;
            $peopleSectionPersons = $peopleSectionParagraph->get('field_person_2')->getValue();
            echo "found people section: \n";
            echo "\ttitle: " . $peopleSectionTitle . "\n";
            echo "\tdescription: " . $peopleSectionDescription . "\n";
            echo "\tpeople: ";
            
            $publicBodyProfilesParagraphs = [];
            
            // iterate through people sections and create new paragraphs for each to attach to this section paragraph
            foreach($peopleSectionPersons as $peopleSectionPerson) {
              $publicBodyProfilesParagraph = Paragraph::create([
                "type" => "public_body_profiles",
              ]);
              $personId = $peopleSectionPerson['target_id'];
              $person = Node::load($personId);
              echo $person->get('field_first_name')->value . ' ' . $person->get('field_last_name')->value . "(" . $person->id() . "), ";
              $publicBodyProfilesParagraph->get('field_profile')->appendItem($peopleSectionPerson);
              $publicBodyProfilesParagraph->save();
              $publicBodyProfilesParagraphs[] = $publicBodyProfilesParagraph;
            }
            echo "\ncreate new profile group paragraph and insert data from previous people section\n";

            $profileGroupParagraph = Paragraph::create([
              "type" => "profile_group",
              "field_title" => $peopleSectionTitle,
              "field_description" => $peopleSectionDescription,
              "field_profiles" => $publicBodyProfilesParagraphs
            ]);
            $profileGroupParagraph->field_description->format = 'sf_restricted_html';
            $profileGroupParagraph->save();
            echo "remove people section with id: " . $peopleSectionParagraph->id() . "\n---\n\n";
            $sectionParagraph->field_content[] = $profileGroupParagraph;
          } else {
            echo "no people sections to update";
          }
        }

        // loop through again with the saved remove ids
        for($i=0; $i<count($peopleSectionRemoveIds); $i++) {
          $removeId = $peopleSectionRemoveIds[$i];
          // get a fresh list of field_content items because each removal rekeys the array
          $contents = $sectionParagraph->get('field_content')->getValue();
          for($j=0; $j<count($contents); $j++) {
            $targetId = $contents[$j]['target_id'];
            if($removeId == $targetId) {
              echo "removing people section with id: $removeId at index: $j\n";
              $sectionParagraph->get('field_content')->removeItem($j);
              $sectionParagraph->save();
            }
          }
        }
      }
    }
  }

  $landingPage->setNewRevision(TRUE);
  $landingPage->revision_log = 'Moved people section data to new profile group';
  $landingPage->setRevisionCreationTime(Drupal::time()->getRequestTime());
  $landingPage->setRevisionUserId($user_id);
  $landingPage->save();
}

/**
 * Move existing people paragraphs to profile groups for other content types
 */
function sfgov_utilities_deploy_02_content_type_profile_group() {
  $contentTypes = [
    [
      "bundle" => "public_body",
      "field_name" => "field_board_members",
    ],
    [
      "bundle" => "department",
      "field_name" => "field_people",
    ],
    [
      "bundle" => "location",
      "field_name" => "field_people",
    ],
  ];

  foreach ($contentTypes as $contentType) {
    $bundle = $contentType["bundle"];
    $fieldName = $contentType["field_name"];
    $nids = \Drupal::entityQuery('node')->condition('type', $bundle)->execute();
    $nodes = Node::loadMultiple($nids);
    foreach($nodes as $node) {
      echo "processing " . $bundle . ":" . $node->getTitle() . "(" . $node->id() . ")\n";
      $people = $node->get($fieldName)->getValue();
      migratePeopleSection($node, $fieldName, $people); 
    }
  }
}

function migratePeopleSection($node, $field_name, $peoples) {
  $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail'=>'webmaster@sfgov.org']);
  $user = reset($users);
  $user_id = $user->id();

  $removeIds = []; // track id's to remove

  foreach($peoples as $people) {
    // collect the profiles
    $profiles = [];
    $peopleParagraphId = $people['target_id'];
    $peopleParagraph = Paragraph::load($peopleParagraphId);
    if ($peopleParagraph->getType() == 'people') {
      $removeIds[] = $peopleParagraphId;
      $peopleParagraphTitle = $peopleParagraph->get('field_people_title')->value;
      $peopleParagraphDescription = $peopleParagraph->get('field_description')->value;
      $persons = $peopleParagraph->get('field_person_2')->getValue();
      $profiles = [];
      echo "profiles:\n";
      foreach($persons as $person) {
        $personNode = Node::load($person['target_id']);
        echo "\t" . $personNode->get('field_first_name')->value . ' ' . $personNode->get('field_last_name')->value . "(" . $personNode->id() . ")\n";
        $profile = Paragraph::create([
          "type" => "public_body_profiles",
        ]);
        $profile->get('field_profile')->appendItem($person);
        $profiles[] = $profile;
        $profile->save();
      }
      $profileGroup = Paragraph::create([
        "type" => "profile_group",
        "field_title" => $peopleParagraphTitle,
        "field_description" => $peopleParagraphDescription,
        "field_profiles" => $profiles
      ]);
      $profileGroup->field_description->format = 'sf_restricted_html';
      $profileGroup->save();
      $node->get($field_name)->appendItem($profileGroup);
    }
  }

  for($i=0; $i<count($removeIds); $i++) {
    $removeId = $removeIds[$i];
    $peoples = $node->get($field_name)->getValue();
    for($j=0; $j<count($peoples); $j++) {
      $people = $peoples[$j];
      $peopleParagraphId = $people['target_id'];
      $peopleParagraph = Paragraph::load($peopleParagraphId);
      if($removeId == $peopleParagraphId) {
        $node->get($field_name)->removeItem($j);
        $peopleParagraph->save();
      }
    }
  }

  $node->setNewRevision(TRUE);
  $node->revision_log = 'Moved people section data to new profile group';
  $node->setRevisionCreationTime(Drupal::time()->getRequestTime());
  $node->setRevisionUserId($user_id);
  $node->save();
}

function sfgov_utilities_deploy_03_resources_migration() {
  $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail'=>'webmaster@sfgov.org']);
  $user = reset($users);
  $user_id = $user->id();

  $nids = \Drupal::entityQuery('node')->condition('type','campaign')->execute();
  $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

  foreach($nodes as $node) {
    $title = $node->getTitle();
    echo $node->$bundle . ": " . $title . "\n";
  }

}
