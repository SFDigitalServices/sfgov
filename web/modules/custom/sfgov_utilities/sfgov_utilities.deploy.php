<?php

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use \Drupal\media\entity\Media;
use Drupal\sfgov_utilities\Utility;
use Drupal\sfgov_utilities\Migration\ResourceMigration\ResourceMigration;
use Drupal\sfgov_utilities\Migration\FieldMigration\TopLevelFieldMigration;

/**
 * Create media entities for existing profile field_photo_images and assign to new field_profile_photo media entity reference
 */
function sfgov_utilities_deploy_00_profile_photos() {
  $nids = \Drupal::entityQuery('node')->condition('type','person')->accessCheck()->execute();
  $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

  $mediaIds = \Drupal::entityQuery('media')->condition('bundle', 'image')->accessCheck()->execute();
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
    $nids = \Drupal::entityQuery('node')->condition('type', $bundle)->accessCheck()->execute();
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

function sfgov_utilities_deploy_03_resources() {
  $rm = new ResourceMigration();
  
  $rm->migrateAboutAndPublicBodyResources();
  $rm->migrateCampaignResources();
  $rm->migrateTopicsAndDepartments();
  $rm->migrateResourceCollections();
}

function sfgov_utilities_deploy_04_resources_subheading() {
  $rm = new ResourceMigration();
  $rm->migrateTopicsAndDepartmentsResourceSubheading();
}

// migrate department content type field_about_description value to field_about_or_description
function sfgov_utilities_deploy_05_dept_page_about() {
  $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail'=>'webmaster@sfgov.org']);
  $user = reset($users);
  $user_id = $user->id();
  
  $nids = \Drupal::entityQuery('node')->condition('type','department')->accessCheck()->execute();
  $nodes = Node::loadMultiple($nids);
  
  foreach($nodes as $node) {
    $nid = $node->id();
    $nodeTitle = $node->getTitle();
    $fieldAboutDescription = $node->field_about_description->value;
    $fieldAboutOrDescription = $node->field_about_or_description->value;
  
    // field_about_or_description is preferred over field_about_description
    // process only if a dept's field_about_or_description is empty and field_about_description is not empty
  
    if(empty($fieldAboutOrDescription) && !empty($fieldAboutDescription)) {
      // print_r($fieldAboutDescription);
      echo "($nid) $nodeTitle\n";
      echo "\t" . $fieldAboutDescription;
      echo "\n";
      $node->field_about_or_description->value = $fieldAboutDescription;
  
      $node->setNewRevision(TRUE);
      $node->revision_log = 'Moved value of field_about_description to field_about_or_description';
      $node->setRevisionCreationTime(Drupal::time()->getRequestTime());
      $node->setRevisionUserId($user_id);
      $node->save();
    }
  }
}

// migrate field_public_body references to field_dept
function sfgov_utilities_deploy_06_field_dept_migration() {
  try {
    // migrate to field_departments from field_dept or field_public_body
    $informationPageNodes = Utility::getNodes('information_page');
    $campaignNodes = Utility::getNodes('campaign');
    $deptTableNodes = Utility::getNodes('department_table');
    $eventNodes = Utility::getNodes('event');
    $formConfirmPageNodes = Utility::getNodes('form_confirmation_page');
    $meetingNodes = Utility::getNodes('meeting');
    $newsNodes = Utility::getNodes('news');
    $resourceCollectionNodes = Utility::getNodes('resource_collection');
    $stepByStepNodes = Utility::getNodes('step_by_step');

    $fieldMigration = new TopLevelFieldMigration();

    $fieldMigration->migrate($informationPageNodes, 'field_public_body', 'field_departments');
    unset($informationPageNodes);

    $fieldMigration->migrate($campaignNodes, 'field_dept', 'field_departments');
    unset($campaignNodes);

    $fieldMigration->migrate($deptTableNodes, 'field_dept', 'field_departments');
    unset($deptTableNodes);

    $fieldMigration->migrate($eventNodes, 'field_dept', 'field_departments');
    unset($eventNodes);

    $fieldMigration->migrate($formConfirmPageNodes, 'field_dept', 'field_departments');
    unset($formConfirmPageNodes);

    $fieldMigration->migrate($meetingNodes, 'field_dept', 'field_departments');
    unset($meetingNodes);

    $fieldMigration->migrate($newsNodes, 'field_dept', 'field_departments');
    unset($newsNodes);

    $fieldMigration->migrate($resourceCollectionNodes, 'field_dept', 'field_departments');
    unset($resourceCollectionNodes);

    $fieldMigration->migrate($stepByStepNodes, 'field_dept', 'field_departments');
    unset($stepByStepNodes);
  } catch(\Exception $e) {
    echo $e->getMessage(), "\n";
  }  
}

/* migrate draft content with old resources to new resources */
function sfgov_utilities_deploy_07_field_dept_migration() {
  $rm = new ResourceMigration();
  
  $rm->migrateAboutAndPublicBodyResources();
  $rm->migrateCampaignResources();
  $rm->migrateResourceCollections();
  $rm->migrateTopicsAndDepartments();
  $rm->migrateTopicsAndDepartmentsResourceSubheading();
}

/* migrate old field_transactions to better field_related_content on transaction content type */
function sfgov_utilities_deploy_08_field_transactions_migration() {
  try {
    $transactionNodes = Utility::getNodes('transaction');
    $relatedServicesFieldMigration = new TopLevelFieldMigration();
    $relatedServicesFieldMigration->migrate($transactionNodes, 'field_transactions', 'field_related_content');
  } catch(\Exception $e) {
    error_log($e->getMessage(), "\n");
  }
}

/* set current departments' include in department list field to true */
function sfgov_utilities_deploy_09_dept_include() {
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
}

/* if department tagged it's parent, be sure to add the department as a division of parent department */
function sfgov_utilities_deploy_10_dept_part_of() {
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
}

/* 
* migrate content from field_divisions to field_agency_sections -> agency section
* migrate content from field_public_bodies to field_departments (related agencies)
*/
function sfgov_utilities_deploy_11_dept_div_pb() {
  try {
    $deptNodes = Utility::getNodes('department');
    $externalUrls = [];
    $problems = [];
  
    foreach($deptNodes as $dept) {
      $deptId = $dept->id();
      // collect things to migrate
      $divisions = $dept->get('field_divisions')->getValue();
      $publicBodies = $dept->get('field_public_bodies')->getValue();

      $agencyContents = [];

      // migrate divisions
      if (!empty($divisions)) {
        // create agency section paragraph with divisions values
        foreach ($divisions as $division) {
          $agencyContent = Paragraph::create([
            "type" => "department_content",
            "field_department" => $division['target_id']
          ]);
          $agencyContents[] = $agencyContent;
        }

        $agencySection = Paragraph::create([
          "type" => "agency_section",
          "field_section_title_list" => "Divisions",
          "field_nodes" => $divisions,
          "field_agencies" => $agencyContents
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

            if (!is_numeric($refNid)) {
              $problems[] = [
                "nid" => $deptId,
                "public_body_url_text" => $text,
                "public_body_url" => $uri
              ];
            }

            if (!empty($refNid)) {
              $relatedAgencies[] = [
                "target_id" => $refNid
              ];
            }
          } else { // other urls, report
            $externalUrls[] = [
              "nid" => $deptId,
              "public_body_url_text" => $text,
              "public_body_url" => $uri,
            ];
          }
        }

        if (!empty($relatedAgencies)) {
          echo "related agencies\n";
          print_r($relatedAgencies);
          echo "\n\n";
        }

        $dept->set('field_departments', $relatedAgencies);
        $dept->set('field_public_bodies', []);
      }

      $dept->save();
    }

    echo "external urls\n";
    print_r($externalUrls);
  
    echo "problems\n";
    print_r($problems);
  } catch (\Exception $e) {
    error_log($e->getMessage());
  }
}

// migrate field_url to field_direct_external_url
function sfgov_utilities_deploy_12_dept_go_to_url() {
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
}
