<?php

/**
 * @file
 * Preprocess functions for 'node' theme hooks.
 */

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\views\Views;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\TypedData\TranslatableInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Implements hook_preprocess_node().
 */
function sfgovpl_preprocess_node(&$variables) {
  // Make sure we have a node object that works on revisions, diffs, etc.
  _sfgovpl_node_object($variables);
  _sfgovpl_negotiate_translation($variables);

  if (!empty($variables['node']) && $variables['node'] instanceof NodeInterface) {
    $node = $variables['node'];
    $content_type = $node->bundle();
    $view_mode = $variables['view_mode'];

    // Full node.
    if ($view_mode == 'full') {
      switch ($content_type) {
        case 'department':
          sfgovpl_add_regions_to_node([
            'department_news',
            'department_events',
          ], $variables);
          break;

        case 'topic':
          $variables['service_section_ids'] = topic_page_services($node);
          break;

        case 'public_body':
          $variables['public_body_fields'] = public_body_page_fields($node);
          break;
        case 'person':
          $variables['profile_fields'] = profile_page_fields($node);
          break;
      }
    }

    // Teaser.
    if ($view_mode == 'teaser') {
      switch ($content_type) {
        case 'department':

          // Set the target for department links on teasers.
          // If the "Go to current URL" field is checked, it sets the target to
          // "_blank". This will print the target on the department teaser
          // template file (node--department--teaser.html.twig).
          $variables['target'] = NULL;
          if ($node->hasField('field_go_to_current_url')) {
            $field_go_to_current_url = $node->get('field_go_to_current_url')
              ->getValue();
            if (!empty($field_go_to_current_url[0]) && $field_go_to_current_url[0]['value'] == '1') {
              $variables['target'] = '_blank';
            }
          }
          break;
      }
    }

    // expose current language to templates
    $variables['language'] = \Drupal::languageManager()->getCurrentLanguage()->getId();
  }
}

/**
 * Implements hook_preprocess_node().
 */
function sfgovpl_preprocess_node__department__full(&$variables) {
  $node = $variables['node'];
  $agencySections = $node->get('field_agency_sections')->getValue();
  $divisionIds = [$node->id()];
  $divisionList = [];
  $currentLanguage = \Drupal::languageManager()->getCurrentLanguage()->getId();
  $typeManager = \Drupal::entityTypeManager();
  $nodeManager = $typeManager->getStorage('node');
  $paragraphManager = $typeManager->getStorage('paragraph');

  foreach ($agencySections as $agencySection) {
    $agencySection = $paragraphManager->load($agencySection['target_id']);
    $agencyContents = !empty($agencySection->field_agencies) ? $agencySection->field_agencies->getValue() : [];

    if (!empty($agencyContents)) {
      foreach($agencyContents as $ac) {
        $agencyContent = $paragraphManager->load($ac['target_id']);
        $divisionId = $agencyContent->get('field_department')->getValue() ? $agencyContent->get('field_department')->getValue()[0]['target_id'] : [];

        if (!empty($divisionId)) {
          $division = $nodeManager->load($divisionId);

          // If current user can view the division, Build a list of titles/urls
          // (using translations if available).
          // [SG-1915]
          // Use this "if" if you need to block both archived and draft.
          // if ($division->access('view')) {
          // Use the "if" below if only needing to block drafts with no current publication, but still allow archived.
          if ($division->get('moderation_state')->getString() != "draft") {
            $divisionTitle = $division->getTitle();
            $divisionUrl = $division->toUrl()->toString();
            if ($division->hasTranslation($currentLanguage)) {
              $divisionTranslation = $division->getTranslation($currentLanguage);
              $divisionTitle = $divisionTranslation->getTitle();
              $divisionUrl = $divisionTranslation->toUrl()->toString();
            }
            $divisionList[] = [
              "title" => $divisionTitle,
              "url" => $divisionUrl,
            ];
          }

          // If show parent meetings is set...
          if ($agencyContent->get('field_show_meeting_parent_agency')->value) {
            $divisionIds[] = $divisionId;
          }
        }
      }
    }
  }

  // pass this agency's id and it's divisions/subcommittees/child agencies ids
  // to the template for rendering meeting blocks.
  $variables['division_ids'] = $divisionIds;
  $variables['division_list'] = $divisionList;

  $variables['count_resources'] = $node->get('field_resources')->referencedEntities();
}

/**
 * Implements hook_preprocess_node().
 */
function sfgovpl_preprocess_node__meeting__full(&$variables) {
  $node = $variables['node'];

  // Determine if the meeting is past.
  if ($date_start = $node->field_smart_date->value) {
    $date_now = (new DateTime())->getTimestamp();
    $variables['meeting_is_past'] = $date_now > $date_start;
  }

  // Get video
  if ($video_par = $node->get('field_videos')->referencedEntities()) {
    try {
      $video_par = $video_par[0];
      $video_type = $video_par->getType();
      if ($video_type == 'video') {
        $video_url = $video_par->field_video->value;
        /** @var \Drupal\sfgov_video\VideoService $video_service */
        $video_service = \Drupal::service('sfgov_video.utilities');
        $video_id = $video_service->getVideoId($video_url);
        // Video captions link
        $url = Url::fromUserInput('/video/' . $video_id);
        $link = Link::fromTextAndUrl(t('Open in another tab'), $url);
        $link = $link->toRenderable();
        $link['#attributes']['target'] = '_blank';
        $link['#attributes']['rel'] = 'noopener';
        // Pass variable with video captions link.
        $variables['video_captions_link'] = $link;
      }
    } catch (Exception $ex) {
      // do nothing
    }
  }
}

/**
 * Implements hook_preprocess_node().
 */
function sfgovpl_preprocess_node__data_story(&$variables) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $variables['node'];
  /** @var \Drupal\paragraphs\ParagraphInterface[] $sections */
  $sections = $node->get('field_content')->referencedEntities();

  $variables['sections'] = [];
  if (!count($sections)) {
    return;
  }

  foreach ($sections as $section) {
    $translatedSection = \Drupal::service('entity.repository')->getTranslationFromContext($section);
    if ($translatedTitle = $translatedSection->get('field_title')->value) {
      $variables['sections'][] = ['title' => $translatedTitle];
    }
  }
}

/**
 * Implements hook_preprocess_node().
 * Creates the "Part of" label when coming from a step by step page
 */
function sfgovpl_preprocess_node__transaction(&$variables) {
  $variables['#cache']['contexts'][] = 'url.query_args';
  $request = \Drupal::request();
  $fromId = $request->query->get('nid');

  if (empty($fromId)) {
    return;
  }

  $node = Node::load($fromId);
  if (!empty($node)) {
    $bundle = $node->bundle();
    $currentLanguage = \Drupal::languageManager()->getCurrentLanguage();
    $currentLanguageId = $currentLanguage->getId();
    $nodeTitle = $node->hasTranslation($currentLanguageId) ?
      $node->getTranslation($currentLanguageId)->getTitle() :
      $node->getTitle();

    if ($bundle === 'step_by_step') {
      $variables['part_of'] = t(ucfirst(str_replace('_', ' ', $bundle))) . ': ' . $nodeTitle;
      $variables['part_of_url'] = $node->toUrl('canonical', [
        'language' => $currentLanguage,
      ])->toString();
    }
  }
}

/**
 * Implements hook_preprocess_node().
 */
function sfgovpl_preprocess_node__data_story__full(&$variables) {
  $manager = \Drupal::entityTypeManager();
  $node_manager = $manager->getStorage('node');
  $paragraph_manager = $manager->getStorage('paragraph');

  /* @var\Drupal\node\NodeInterface $node */
  $node = $variables['node'];
  $nid = $node->id(); // 1917
  $lang = $node->get('langcode')->value;

  // We need to find resource nodes that have nested references to this data
  // story node.
  // First, we get the latest version of any subsection paragraphs
  // that reference back to this data story node.
  $data_subsections = $paragraph_manager->getQuery()
    ->accessCheck()
    ->latestRevision()
    ->exists('field_data_story')
    ->condition('langcode', $lang)
    ->condition('type', 'data_story_reference_subsection')
    ->condition('field_data_story.target_id', $nid)
    ->execute();

  if (!empty($data_subsections)) {
    $nodes_for_links = [];
    $links = [];
    // Next, we get the latest version of any section paragraphs that reference
    // (are the parent of) the subsections we found above.
    $data_sections = $paragraph_manager->getQuery()
      ->accessCheck()
      ->latestRevision()
      ->exists('field_content')
      ->condition('langcode', $lang)
      ->condition('type', 'data_story_reference_section')
      ->condition('field_content.target_id', $data_subsections, 'IN')
      ->execute();

    if (!empty($data_sections)) {
      // Next, we get that resource nodes that are the parent of the sections we
      // found above.
      $resource_nodes = $node_manager->getQuery()
        ->accessCheck()
        ->latestRevision()
        ->exists('field_paragraphs')
        ->condition('langcode', $lang)
        ->condition('status', 1)
        ->condition('type', 'resource_collection')
        ->condition('field_paragraphs.target_id', $data_sections, 'IN')
        ->execute();

      // Because of the order of locating this info, We don't know if the latest
      // versions of the resources found above still reference back to this data
      // story node. The queries can return past version references by default.
      // We need to cycle thru the latest version of each resource node we found and
      // check that it still has a reference back to this data story.
      if (!empty($resource_nodes)) {
        foreach ($resource_nodes as $rvid => $rnid) {
          // load the latest version of the resource node.
          $resource_node = $node_manager->loadRevision($rvid);
          if ($resource_node->hasField('field_paragraphs')) {
            $ds_sections = $resource_node->get('field_paragraphs')
              ->referencedEntities();
            foreach ($ds_sections as $section) {
              if ($section->hasField('field_content')) {
                $ds_subsections = $section->get('field_content')
                  ->referencedEntities();
                foreach ($ds_subsections as $subsection) {
                  if ($subsection->hasField('field_data_story')) {
                    $data_stories = $subsection->get('field_data_story')
                      ->referencedEntities();
                    foreach ($data_stories as $story) {
                      // For each data story that is referenced at the deepest level
                      // of the resource node, if it has a match to our current data
                      // story node, that means it can be added to our link list.
                      if ($story->id() == $nid) {
                        $nodes_for_links[$resource_node->id()] = [
                          'id' => $resource_node->id(),
                          'label' => $resource_node->label()
                        ];
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    foreach ($nodes_for_links as $link) {
      $links[] = Link::fromTextAndUrl(
        $link['label'],
        Url::fromRoute('entity.node.canonical', ['node' => $link['id']])
      )->toString();
    }
  }

  $build = [
    '#markup' => !empty($links) ? t('Part of ') . implode(', ', $links) : '',
    '#cache' => ['tags' => ['node_list:resource_collection']]
  ];

  $variables['part_of'] = \Drupal::service('renderer')->render($build);
}

/**
 * Implements hook_preprocess_node().
 */
function sfgovpl_preprocess_node__report__full(&$variables) {
  /* @var\Drupal\node\NodeInterface $node */
  $node = $variables['node'];

  $variables['print_version_url'] = NULL;
  if ($node->hasField('field_print_version') && ($media_entities = $node->field_print_version->referencedEntities())) {
    $media = reset($media_entities);
    $variables['print_version_url'] = \Drupal::service('file_url_generator')->generateAbsoluteString($media->field_media_file->entity->getFileUri());
    $variables['print_version_label'] = $media->label();
  }
}

/**
 * Implements hook_preprocess_node().
 */
function sfgovpl_preprocess_node__person__card_with_image_small(&$variables) {
  $nid = $variables['node']->id();

  if (\Drupal::routeMatch()->getParameter('node')) {
    $parent_node = \Drupal::routeMatch()->getParameter('node');
    $parent_node_type = $parent_node->getType();
    $parent_nid = $parent_node->id();
    $profileGroupTypes = ['public_body', 'department', 'location', 'landing'];

    if ($parent_nid != $nid && in_array($parent_node_type, $profileGroupTypes)) {
      $variables['parent_node_id'] = [
        '#markup' => $parent_nid,
        '#cache' => [
          'max-age' => 0
        ],
      ];

      $variables['parent_is_front'] = [
        '#markup' => \Drupal::service('path.matcher')->isFrontPage(),
        '#cache' => [
          'max-age' => 0
        ]
      ];
    }
  }
}

/**
 * Implements hook_preprocess_node().
 */
function sfgovpl_preprocess_node__person__full(&$variables) {
  $manager = \Drupal::entityTypeManager();
  $paragraph_manager = $manager->getStorage('paragraph');
  $node_manager = $manager->getStorage('node');
  $node = $variables['elements']['#node'];

  // [SG-1813]
  // Current position information.
  $current_job_title = !empty($node->get('field_title')) ? $node->get('field_title')->getValue()[0]['value'] : NULL;

  $current_department = $node->get('field_city_department')->getValue();
  $current_ids = [];
  foreach ($current_department as $item) {
    $current_ids[] = $item['target_id'];
  }

  // [SG-1813]
  // Held position information.
  $held_position = [];
  if ($node->hasField('field_profile_positions_held')) {
    $positions_held = $node->get('field_profile_positions_held')->getValue();
    foreach ($positions_held as $delta => $position) {
      $p = $paragraph_manager->load($position['target_id']);
      $dept = $p->get('field_department')->getValue();
      $dept_id = !empty($dept[0]['target_id']) ? $dept[0]['target_id'] : NULL;
      $pos = $p->get('field_commission_position')->getValue();
      $job_title = !empty($pos[0]['value']) ? $pos[0]['value'] : NULL;

      // Gather an array of held position department ID's and job titles.
      $held_position[$delta] = [
        'id' => $dept_id,
        'title' => $job_title,
      ];
    }
  }

  // [SG-1813]
  // Clear out redundant and duplicate positions/departments.
  $existing_ids = [];
  foreach ($held_position as $i => $position) {
    $remove = FALSE;

    // If the held department and title match the current department and title,
    // remove.
    if (in_array($position['id'], $current_ids) && ($position['title'] == $current_job_title)) {
      $remove = TRUE;
    }

    // If the held department matches the current department and held job title
    // is empty, remove.
    if (in_array($position['id'], $current_ids) && empty($position['title'])) {
      $remove = TRUE;
    }

    // If the department has already been printed, and has no job title, remove.
    if (in_array($position['id'], $existing_ids) && empty($position['title'])) {
      $remove = TRUE;
    }

    // If any of the above criteria was met, remove the position from display.
    if ($remove) {
      unset($variables['content']['field_profile_positions_held'][$i]);
    }

    $existing_ids[] = $position['id'];
  }
}

/**
 * Retrieve the transaction ids in field_department_services that will be
 * excluded from "more services" section on topic page send the list of ids to a
 * Twig var which will then get passed to view contextual filters via
 * twig_tweak.
 */
function topic_page_services($node) {
  if (!empty($node)) {
    if($node->hasField('field_department_services')) {
      $services = $node->get('field_department_services');
      if(!empty($services->getValue())) {
        $txs = array();
        foreach($services as $service) {
          $serviceSectionId = $service->getValue()['target_id'];
          $serviceSectionLoaded = Paragraph::load($serviceSectionId);

          // Load the paragraph and get the transactions.
          $topicTransactions = $serviceSectionLoaded->get('field_dept_service_sect_services')->getValue();
          for($i=0; $i<count($topicTransactions); $i++) {
            $txId = $topicTransactions[$i]['target_id'];
            $txs[] = $txId;
          }
        }
        return $txs;
      }
    }
  }
  return null;
}

function public_body_page_fields($node) {
  $currentLanguageId = \Drupal::languageManager()->getCurrentLanguage()->getId();
  $styleThumbnail = ImageStyle::load('thumbnail');
  $theFields = array(
    'featured_items' => array(),
    'services' => array(),
  );

  if($node) {
    if($node->hasField('field_people')) {
      $aboutPeople = $node->get('field_people');
      if($aboutPeople && !empty($aboutPeople->getValue())) {
        $peopleId = $aboutPeople->getValue()[0]['target_id'];
        $peopleParagraph = Paragraph::load($peopleId);
        if(!empty($peopleParagraph->get('field_person_2')->getValue())) {
          $people = $peopleParagraph->get('field_person_2')->getValue();
          foreach($people as $person) {
            $personNode = Node::load($person['target_id']);
            if($personNode->get('field_profile_photo')->entity->uri->value) {
              $theFields['people'][] = array(
                'first_name' => $personNode->get('field_first_name')->getValue()[0]['value'],
                'last_name' => $personNode->get('field_last_name')->getValue()[0]['value'],
                'title' => $personNode->get('field_title')->getValue()[0]['value'],
                'url' => $personNode->get('field_direct_external_url')->getValue() ? $personNode->get('field_direct_external_url')->getValue()[0]['uri'] : '',
                'photo' => $styleThumbnail->buildUrl($personNode->get('field_profile_photo')->entity->uri->value),
              );
            } elseif($personNode->get('field_photo')->entity->uri->value) {
              $theFields['people'][] = array(
                'first_name' => $personNode->get('field_first_name')->getValue()[0]['value'],
                'last_name' => $personNode->get('field_last_name')->getValue()[0]['value'],
                'title' => $personNode->get('field_title')->getValue()[0]['value'],
                'url' => $personNode->get('field_direct_external_url')->getValue() ? $personNode->get('field_direct_external_url')->getValue()[0]['uri'] : '',
                'photo' => $styleThumbnail->buildUrl($personNode->get('field_photo')->entity->uri->value),
              );
            }
          }
        }
      }
    }

    if($node->hasField('field_address')) {
      $addressId = $node->get('field_address')->getValue() ? $node->get('field_address')->getValue()[0]['target_id'] : null;
      if($addressId) {
        $addressEntity = \Drupal::entityTypeManager()->getStorage('location')->load($addressId);
        if($addressEntity) {
          $addressValues = $addressEntity->get('field_address')->getValue()[0];
          $theFields['address']['line1'] = $addressValues['address_line1'];
          $theFields['address']['line2'] = $addressValues['address_line2'];
          $theFields['address']['country_code'] = $addressValues['country_code'];
          $theFields['address']['city'] = $addressValues['locality'];
          $theFields['address']['state'] = $addressValues['administrative_area'];
          $theFields['address']['zip'] = $addressValues['postal_code'];
          $theFields['address']['organization'] = $addressValues['organization'];
          $hours = $addressEntity->get('field_operating_hours')->getValue();
          usort($hours, function($item1, $item2) {
            return $item1['day'] <=> $item2['day'];
          });
          foreach($hours as $hour) {
            $theFields['address']['hours'][] = array(
              'day' => jddayofweek($hour['day'], 2),
              'start' => date("g:i a", strtotime($hour['starthours'])),
              'end' => date("g:i a", strtotime($hour['endhours'])),
              'comment' => $hour['comment'],
            );
          }
          $theFields['map'] = _sfgov_utilities_build_map($addressValues);
        }
      }
    }

    if($node->hasField('field_phone_numbers')) {
      $phoneNumbers = $node->get('field_phone_numbers');
      if($phoneNumbers->getValue()) {
        foreach($phoneNumbers->getValue() as $phoneNumber) {
          $phoneNumberId = $phoneNumber['target_id'];
          $phoneParagraph = Paragraph::load($phoneNumberId);
          $number = $phoneParagraph->get('field_tel')->getValue()[0]['value'];
          $number = preg_replace("/[^\d]/","",$number); // remove non-digit characters
          $length = strlen($number);
          if($length == 10) {
            $number = preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $number);
          }
          $phoneDetails = $phoneParagraph->get('field_text')->getValue() ? $phoneParagraph->get('field_text')->getValue()[0]['value'] : null;
          $phoneOwner = $phoneParagraph->get('field_owner')->getValue() ? $phoneParagraph->get('field_owner')->getValue()[0]['value'] : null;
          $theFields['phone_numbers'][] = array(
            'number' => $number,
            'details' => $phoneDetails,
            'owner' => $phoneOwner,
          );
        }
      }
    }

    if($node->hasField('field_email')) {
      $emailField = $node->get('field_email');
      if($emailField->getValue()) {
        $emailId = $emailField->getValue()[0]['target_id'];
        $emailParagraph = Paragraph::load($emailId);
        $email = $emailParagraph->get('field_email')->getValue() ? $emailParagraph->get('field_email')->getValue()[0]['value'] : null;
        $emailTitle = $emailParagraph->get('field_title')->getValue() ? $emailParagraph->get('field_title')->getValue()[0]['value'] : null;
        if ($email) {
          $theFields['email']['email_address'] = $email;
        }
        if ($emailTitle) {
          $theFields['email']['email_title'] = $emailTitle;
        }
      }
    }

    if($node->hasField('field_about_description')) {
      $aboutField = $node->get('field_about_description');
      if($aboutField->getValue()) {
        $aboutValue = $aboutField->getValue()[0]['value'];
        $theFields['about'] = $aboutValue ? $aboutValue : null;
      }
    }

    if($node->hasField('field_departments')) {
      $departmentsField = $node->get('field_departments');
      if($departmentsFieldValue = $departmentsField->getValue()) {
        $departments = [];
        foreach ($departmentsFieldValue as $key => $value) {
          $dept = Node::load($value['target_id']);
          $departments[$key] = [
            'title' => $dept->label(),
            'description' => $dept->field_description ? $dept->field_description->value : '',
            'url' => $dept->toUrl()->toString(),
          ];
        }
        $theFields['departments'] = count($departments) ? $departments : null;
      }
    }
  }

  if ($otherInfo = $node->get('field_other_info')->getValue()) {
    foreach ($otherInfo as $otherInfoKey => $value) {
      $otherInfoParagraph = Paragraph::load($value['target_id']);
      $theFields['other_info'][$otherInfoKey] = [
        'bundle' => $otherInfoParagraph->bundle(),
        'title' => $otherInfoParagraph->hasTranslation($currentLanguageId) ? $otherInfoParagraph->getTranslation($currentLanguageId)->get('field_title')->value : $otherInfoParagraph->field_title->value,
        'content' => [],
      ];

      switch ($otherInfoParagraph->bundle()) {
        case 'other_info_card':
          foreach ($otherInfoParagraph->get('field_resources')->getValue() as $value) {
            $otherInfoContent = Paragraph::load($value['target_id']);
            $id = '';
            $title = '';
            $description = '';
            $url = '';
            if($otherInfoContent->getType() == 'resource_entity') {
              $resource_storage = \Drupal::entityTypeManager()->getStorage('resource');
              if(!empty($otherInfoContent->get('field_resource')->getValue())) {
                /** @var \Drupal\eck\Entity\EckEntity $resource */
                $resource = $resource_storage->load($otherInfoContent->get('field_resource')[0]->target_id);
                $url = $resource->get('field_url')->uri ?? '';
                $title = $resource->get('title')->value;
                $description = $resource->get('field_description')
                  ? $resource->field_description->value
                  : \Drupal::service('renderer')->render($resource->get('field_description')->value);
              }
            } else if($otherInfoContent->getType() == 'resource_node') {
              $node_storage = \Drupal::entityTypeManager()->getStorage('node');
              if(!empty($otherInfoContent->get('field_node')->getValue())) {
                /** @var \Drupal\node\Entity\Node $node */
                $node = $node_storage->load($otherInfoContent->get('field_node')[0]->target_id);
                $id = $node->id();
                $description = '';
                if($node->hasField('field_description')) {
                  $description = $node->get('field_description') ? $node->field_description->value : \Drupal::service('renderer')->render($node->get('field_description')->value);
                }
                $title = $node->getTitle();
                $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id());
              }
            } else {
              $id = $otherInfoContent->id();
              $title = $otherInfoContent->field_title->value;
              $description = $otherInfoContent->field_description ? $otherInfoContent->field_description->value : NULL;
              $url = $otherInfoContent->field_link->uri ? Url::fromUri($otherInfoContent->field_link->uri)->toString() : NULL;
            }

            if(!empty($title)) {
              $theFields['other_info'][$otherInfoKey]['content'][] = [
                'id' => $id,
                'title' => $title,
                'description' => $description,
                'url' => $url,
              ];
            }
          }
          break;
        case 'other_info_document':
          foreach ($otherInfoParagraph->field_file as $value) {
            $theFields['other_info'][$otherInfoKey]['content'][] = [
              'label' => !empty($value->entity) ? $value->entity->label() : '',
              'url' => !empty($value->entity->field_media_file->entity) ? \Drupal::service('file_url_generator')->generateAbsoluteString($value->entity->field_media_file->entity->getFileUri()) : NULL,
              'link' => !empty($value->entity->field_document_url->uri) ? Url::fromUri($value->entity->field_document_url->uri)->toString() : NULL,
            ];
          }
          break;
      }
    }
  }

  return $theFields;
}

/**
 * Prepare profile field data from entities referencing the profile
 */
function profile_page_fields($node) {
  $theFields = [];

  if($node) {
    // Get field data related to profile
    $nid = $node->id();

    // Get social media field data
    if($node->hasField('field_social_media')) {
      $aboutSocial = $node->get('field_social_media');
      if($aboutSocial && $aboutSocial->getValue()) {
        $socialMediaLabel = $aboutSocial->getFieldDefinition()->getLabel();
        $theFields['social']['title'] = $socialMediaLabel;
        $aboutSocialId = $aboutSocial->getValue()[0]['target_id'];
        if ($aboutSocialParagraph = Paragraph::load($aboutSocialId)) {
          $socialFields = $aboutSocialParagraph->getFields();
          foreach($socialFields as $socialField) {
            if($socialField->getFieldDefinition()->getType() == 'link') {
              $socialFieldKey = $socialField->getFieldDefinition()->getName();
              $socialLabel = $socialField->getFieldDefinition()->getLabel();
              $socialUrl = $aboutSocialParagraph->get($socialFieldKey)->getValue() ? $aboutSocialParagraph->get($socialFieldKey)->getValue()[0]['uri'] : null;
              if($socialUrl) {
                $theFields['social']['values'][] = array(
                  'label' => $socialLabel,
                  'url' => $socialUrl,
                );
              }
            }
          }
        }
      }
    }

    // Address field data
    if($node->hasField('field_address')) {
      $addressId = $node->get('field_address')->getValue() ? $node->get('field_address')->getValue()[0]['target_id'] : null;
      // If no profile address check city dept field
      if(!$addressId) {
        if($node->hasField('field_city_department')) {
          $deptId = $node->get('field_city_department')->getValue() ? $node->get('field_city_department')->getValue()[0]['target_id'] : null;
          if($deptId) {
            // Get department address id
            $dept_node =  Node::load(intval($deptId));
            if(!empty($dept_node)) {
              $addressId = $dept_node->get('field_address')->getValue() ? $dept_node->get('field_address')->getValue()[0]['target_id'] : null;
              if($addressId) {
                $addressEntity = \Drupal::entityTypeManager()->getStorage('location')->load($addressId);
              }
            }
          }
        }
      }
      if($addressId) {
        $addressEntity = \Drupal::entityTypeManager()->getStorage('location')->load($addressId);
      }
      if(isset($addressEntity)) {
        $addressValues = $addressEntity->get('field_address')->getValue()[0];
        $department = FALSE;
        if (!$addressEntity->get('field_department')->isEmpty()) {
          $department = \Drupal::entityTypeManager()->getStorage('node')->load($addressEntity->get('field_department')->target_id);
          $Addresstitle = $department ? $department->label() : '';
        } else {
          $Addresstitle = $addressValues['organization'] ?: $addressValues['addressee'] ?: $addressValues['location_name'];
        }
        // Choose a title value in descending order.
        $theFields['address']['title'] = $Addresstitle;
        // If one of the address fields was used as the title, remove it from
        // the render array so that it doesn't show up twice.
        $theFields['address']['organization'] = ($addressValues['organization'] == $Addresstitle) ? NULL : $addressValues['organization'];
        $theFields['address']['addressee'] = ($addressValues['addressee'] == $Addresstitle) ? NULL : $addressValues['addressee'];
        $theFields['address']['location_name'] = ($addressValues['location_name'] == $Addresstitle) ? NULL : $addressValues['location_name'];

        $theFields['address']['line1'] = $addressValues['address_line1'];
        $theFields['address']['line2'] = $addressValues['address_line2'];
        $theFields['address']['country_code'] = $addressValues['country_code'];
        $theFields['address']['city'] = $addressValues['locality'];
        $theFields['address']['state'] = $addressValues['administrative_area'];
        $theFields['address']['zip'] = $addressValues['postal_code'];
        $hours = $addressEntity->get('field_operating_hours')->getValue();
        usort($hours, function($item1, $item2) {
          return $item1['day'] <=> $item2['day'];
        });
        foreach($hours as $hour) {
          $theFields['address']['hours'][] = array(
            'day' => jddayofweek($hour['day'], 2),
            'start' => date("g:i a", strtotime($hour['starthours'])),
            'end' => date("g:i a", strtotime($hour['endhours'])),
            'comment' => $hour['comment'],
          );
        }
        $theFields['map'] = _sfgov_utilities_build_map($addressValues);
      }
    }

    // Biography
    if($node->hasField('body')) {

      // Get trimmed profile biography text.
      $source = \Drupal::service('entity.repository')->getTranslationFromContext($node);
      $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder('node');

      if ($source && $source->hasField('body') && $source->access('view')) {
        $value = $source->get('body');

        // Get the value of the trimmed biography text that comes from the
        // teaser view mode settings.
        $output_trim = $viewBuilder->viewField($value, 'teaser');
        $output_trim = $output_trim[0] ?? [];

        // Remove odd items from trimmed text (like '&#13;').
        if (isset($output_trim['#output']['#text'])) {
          $output_trim['#output']['#text'] = str_replace("&#13;", "", $output_trim['#output']['#text']);
        }

        // [SG-1814]
        // Changing the format of the trimmed text from the teaser, to restrict-
        // ed_html or full_html, helps to clear out the extra <p> and </br>
        // tags. sf_restricted_html or sf_basic_html formats don't have the
        // setup to clear that stuff out.
        $output_trim['#format'] = 'full_html';

        // Get the biography text from the full display (default) view mode
        // settings.
        $output_full = $viewBuilder->viewField($value, 'default');
        $output_full = $output_full[0] ?? [];

        // If the full text is longer than the trimmed text, it means we need to
        // output as the biotrim "See More"/"See Less" expanding style.
        if (!empty($output_full['#text']) && (strlen($output_full['#text']) > strlen($output_trim['#text']))) {
          $output_trim['#cache']['tags'] = $source->getCacheTags();
          $theFields['biotrim'] = !empty($output_trim) ? $output_trim : [];
        }
      }
    }
  }

  return $theFields;
}

/**
 * Helper function for building profile page data
 */
function sfgovpl_profile_reference_nodes($ref_nids, $reftype, $nid) {
  $currentLanguage = \Drupal::languageManager()->getCurrentLanguage()->getId();

  // Build department data for profile page
  foreach($ref_nids as $key => $value) {
    $node =  Node::load(intval($value['entity_id']));
    $node = $node->hasTranslation($currentLanguage) ? $node->getTranslation($currentLanguage) : $node;
    $ref_data[$key]['profile']['node_title'] = $node->label();
    $ref_data[$key]['profile']['node_url'] = $node->toUrl()->toString();

    // Address
    $address = $node->hasField('field_address') ? $node->get('field_address')->referencedEntities() : [];
    if (!empty($address)) {
      $ref_data[$key]['profile']['address'] = $address[0]->get('field_address')->getValue();
    }

    // Email
    $email = $node->hasField('field_email') ? $node->get('field_email')->referencedEntities() : [];
    if (!empty($email)) {
      foreach ($email as $ekey => $eitem) {
        $ref_data[$key]['profile']['email'][$ekey]['title'] = $eitem->get('field_title')->value;
        $ref_data[$key]['profile']['email'][$ekey]['email_address'] = $eitem->get('field_email')->value;
      }
    }

    // Phone
    $phone = $node->hasField('field_phone_numbers') ? $node->get('field_phone_numbers')->referencedEntities() : [];
    if (!empty($phone)) {
      foreach($phone as $pkey => $pitem) {
        $ref_data[$key]['profile']['phone'][$pkey]['tel'] = $pitem->hasField('field_tel') ? $pitem->get('field_tel')->value : '';
        $ref_data[$key]['profile']['phone'][$pkey]['text'] = $pitem->hasField('field_text') ? $pitem->get('field_text')->value : '';
        $ref_data[$key]['profile']['phone'][$pkey]['owner'] = $pitem->hasField('field_owner') ? $pitem->get('field_owner')->value : '';
      }
    }

    // Job titles
    if ($reftype == 'public body') {
      $profile_field = 'field_board_members';
    }
    if ($reftype == 'department') {
      $profile_field = 'field_people';
    }

    // Set source type
    $ref_data[$key]['profile']['source_type'] = $reftype;

    if ($people = $node->get($profile_field)->referencedEntities()) {
      foreach($people as $pkey => $pitem) {
        $bundle = $pitem->bundle();
        if($bundle == 'profile_group') {
          $profiles = $pitem->get('field_profiles')->getValue();
          foreach ($profiles as $rkey => $ritem) {
            $profile = Paragraph::load($ritem['target_id']);
            $pnid = $profile->get('field_profile')->getValue();
            if ($pnid[0]['target_id'] == $nid) {
              $ref_data[$key]['profile']['job']['position'] = $profile->get('field_commission_position')->value;
              $ref_data[$key]['profile']['job']['profile'] = $profile->get('field_profile')->value;
              $ref_data[$key]['profile']['job']['position_type'] = $profile->get('field_position_type')->value;
              if ($temp = $profile->get('field_starting_year')->value) {
                $sdate = new DateTime($temp);
              }
              if (isset($sdate)) {
                $ref_data[$key]['profile']['job']['start_year'] = $sdate ? $sdate->format('Y') : '';
                $ref_data[$key]['profile']['job']['stime'] = $temp;
              }
              if ($temp = $profile->get('field_ending_year')->value) {
                $edate = new DateTime($temp);
              }
              if (isset($edate)) {
                $ref_data[$key]['profile']['job']['end_year'] = $edate ? $edate->format('Y') : '';
                $ref_data[$key]['profile']['job']['etime'] = $temp;
              }
            }
          }
        }
      }
    }
  }
  if (isset($ref_data)) {
    return $ref_data;
  }
}

/**
 * Helper function to facilitate rendering blocks within a node template.
 *
 * @see sfgovpl_preprocess_node().
 */
function sfgovpl_add_regions_to_node(array $regions, &$variables) {
  // Retrieve active theme
  $theme = \Drupal::theme()->getActiveTheme()->getName();
  $available_regions = system_region_list($theme);
  // Validate allowed regions with available regions
  $regions = array_intersect(array_keys($available_regions), $regions);
  foreach ($regions as $key => $region) {
    $blocks = \Drupal::entityTypeManager()->getStorage('block')->loadByProperties(array('theme' => $theme, 'region' => $region));
    uasort($blocks, 'Drupal\block\Entity\Block::sort');
    // Capture viewable blocks and their settings to $build
    $build = array();
    foreach ($blocks as $key => $block) {
      if ($block->access('view')) {
        $builder = \Drupal::entityTypeManager()->getViewBuilder('block');
        $build[$key] = $builder->view($block);
      }
    }
    // Add build to region
    $variables[$region] = $build;
  }
}

/**
 * Helper function for managing human- vs machine- translations.*
 * Extends sfgovpl_preprocess_node().
 * @see README.md for template advice.
 */
function _sfgovpl_negotiate_translation(&$variables) {
  if (!empty($variables['node']) && $variables['node'] instanceof NodeInterface) {
    $node = $variables['node'];
    $view_mode = $variables['view_mode'] ?? '';
    $variables = array_merge($variables, _sfgovpl_node_notranslate($node, $view_mode));
  }
  return $variables;
}
