<?php

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\eck\Entity\EckEntityBundle;
use Drupal\Core\Entity;
use Drupal\views\Views;

/**
 * Implements hook_preprocess_node().
 */
function sfgovpl_preprocess_node(&$variables) {
  if(!empty($variables['node'])) {
    $content_type = $variables['node']->getType();
    if($content_type == 'department') {
      $variables['dept_fields'] = dept_page_fields();
      sfgovpl_add_regions_to_node(['department_news'], $variables);
      sfgovpl_add_regions_to_node(['department_events'], $variables);
    }
    if($content_type == 'topic') {
      $variables['service_section_ids'] = topic_page_services();
    }
  }
}

/*
* retrieve the transaction ids in field_department_services that will be excluded from "more services" section on topic page
* send the list of ids to a twig var which will then get passed to view contextual filters via twig_tweak
*/
function topic_page_services() {
  $node = \Drupal::request()->attributes->get('node');
  if($node) {
    $services = $node->get('field_department_services');
    if(!empty($services->getValue())) {
      foreach($services as $service) {
        $serviceSectionId = $service->getValue()['target_id'];
        $serviceSectionLoaded = Paragraph::load($serviceSectionId);
        
        // load the paragraph and get the transactions
        $topicTransactions = $serviceSectionLoaded->get('field_dept_service_sect_services')->getValue();
        $txs = array();
        for($i=0; $i<count($topicTransactions); $i++) {
          $txId = $topicTransactions[$i]['target_id'];
          $txs[] = $txId;
        }
      }
      return $txs;
    }
  }
  return null;
}

function dept_page_fields() {
  $node = \Drupal::request()->attributes->get('node');
  $theFields = array(
    'featured_items' => array(),
    'services' => array(),
  );
  if($node) {
    if($node->hasField('field_featured_items')) {
      $featuredItems = $node->get('field_featured_items');
      foreach($featuredItems as $featuredItem) {
        $featuredItemParagraphId = $featuredItem->getValue()['target_id'];
        $featuredItemLoaded = Paragraph::load($featuredItemParagraphId);
        $featuredItemTitle = $featuredItemLoaded->get('field_feature_title')->getValue()[0]['value'];
        $featuredItemDescription = $featuredItemLoaded->get('field_description')->getValue()[0]['value'];
        $featuredItemUrl = $featuredItemLoaded->get('field_feature_link')->getValue()[0]['uri'];
        $theFields['featured_items'][] = array(
          'title' => $featuredItemTitle,
          'description' => $featuredItemDescription,
          'url' => $featuredItemUrl,
        );
      }
    }
    if($node->hasField('field_department_services')) {
      $deptServices = $node->get('field_department_services');
      $deptServicesSectionLabel = $deptServices->getFieldDefinition()->getLabel();
      $theFields['services']['label'] = $deptServicesSectionLabel;
      if(!empty($deptServices->getValue())) {
        foreach($deptServices as $deptService) {
          $deptServiceSectionId = $deptService->getValue()['target_id'];
          $deptServiceSectionLoaded = Paragraph::load($deptServiceSectionId);
          $deptServiceSectionTitle = $deptServiceSectionLoaded->get('field_dept_service_section_title')->getValue()[0]['value'];
          
          // load the paragraph and get the transactions
          $deptTransactions = $deptServiceSectionLoaded->get('field_dept_service_sect_services')->getValue();
          $txs = array();
          for($i=0; $i<count($deptTransactions); $i++) {
            $txId = $deptTransactions[$i]['target_id'];
            $tx = Node::load($txId);
            $txs[] = array(
              'tx_title' => $tx->get('title')->getValue()[0]['value'],
              'tx_description' => $tx->get('field_description')->getValue()[0]['value'],
              'tx_url' => $tx->toUrl('canonical', ['absolute'=>true])->toString(),
            );
          }
          $theFields['services']['values'][] = array(
            'title' => $deptServiceSectionTitle,
            'transactions' => $txs,
          );
        }
      } else {
        $view = Views::getView('services');
        $view->setDisplay('block_2');
        $view->execute();
        $results = $view->result;
        $txs = array();
        foreach($results as $result) {
          $txs[] = array(
            'tx_title' => $result->_entity->get('title')->value,
            'tx_description' => $result->_entity->get('field_description')->value,
            'tx_url' => $result->_entity->toUrl('canonical', ['absolute'=>true])->toString(),
          );
        }
        if(count($txs) > 0) {
          $theFields['services']['values'][] = array(
            'title' => '',
            'transactions' => $txs,
          );
        }
      }
    }
    if($node->hasField('field_spotlight')) {
      $deptSpotlight = $node->get('field_spotlight');
      if(!empty($deptSpotlight) && !empty($deptSpotlight->getValue())) {
        $deptSpotlightParagraphId = $deptSpotlight->getValue()[0]['target_id'];
        $deptSpotlightParagraph = Paragraph::load($deptSpotlightParagraphId);
        $spotlightTitle = $deptSpotlightParagraph->get('field_title')->getValue()[0]['value'];
        $spotlightDescription = $deptSpotlightParagraph->get('field_description')->getValue()[0]['value'];
        $spotlightButtonId = $deptSpotlightParagraph->get('field_spotlight_button')->getValue()[0]['target_id'];
        $spotlightButtonParagraph = Paragraph::load($spotlightButtonId);
        $spotlightButtonTitle = $spotlightButtonParagraph->get('field_link')->getValue()[0]['title'];
        $spotlightButtonUri = $spotlightButtonParagraph->get('field_link')->getValue()[0]['uri'];
        $spotlightImage = $deptSpotlightParagraph->get('field_spotlight_img');
        $spotlightImageUrl = null;
        if($spotlightImage) {
          if($spotlightImage->entity) {
            $spotlightImageUrl = file_create_url($spotlightImage->entity->field_media_image->entity->getFileUri());
          }
        }
        // $spotlightImageUrl = $spotlightImage ? file_create_url($spotlightImage->entity->field_media_image->entity->getFileUri()) : null;
        $theFields['spotlight']['title'] = $spotlightTitle;
        $theFields['spotlight']['description'] = $spotlightDescription;
        $theFields['spotlight']['button_text'] = $spotlightButtonTitle;
        $theFields['spotlight']['button_uri'] = $spotlightButtonUri;
        if($spotlightImageUrl) {
          $theFields['spotlight']['image_url'] = $spotlightImageUrl;
        }
      }
    }
    if($node->hasField('field_resources')) {
      $resources = $node->get('field_resources');
      $resourcesLabel = $resources->getFieldDefinition()->getLabel();
      $theFields['resources']['label'] = $resourcesLabel;
      foreach($resources as $resource) {
        $resourceParagraphId = $resource->getValue()['target_id'];
        $resourceParagraph = Paragraph::load($resourceParagraphId);
        $resourceItemTitle = $resourceParagraph->get('field_title')->getValue()[0]['value'];
        $resourceItemDescription = $resourceParagraph->get('field_description')->getValue()[0]['value'];
        $resourceItemUrl = $resourceParagraph->get('field_link')->getValue()[0]['uri'];
        $theFields['resources']['items'][] = array(
          'title' => $resourceItemTitle,
          'description' => $resourceItemDescription,
          'url' => $resourceItemUrl,
        );
      }
    }
    if($node->hasField('field_url')) {
      $theUrl = $node->get('field_url')->getValue();
      $deptUrl =  $theUrl ? $theUrl : null;
      $theFields['dept_url'] = $deptUrl;
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
        $theFields['email']['email_address'] = $email ? $email : null;
        $theFields['email']['email_title'] = $emailTitle ? $emailTitle : null;
      }
    }

    // about fields
    if($node->hasField('field_about_description')) {
      $aboutDescription = $node->get('field_about_description')->getValue() ? $node->get('field_about_description')->getValue()[0]['value'] : null;
      if($aboutDescription) {
        $theFields['about']['description'] = $aboutDescription;
      }
    }
    if($node->hasField('field_divisions')) {
      $aboutDivisions = $node->get('field_divisions')->getValue();
      if($aboutDivisions) {
        foreach($aboutDivisions as $aboutDivision) {
          $divisionDept = Node::load($aboutDivision['target_id']);
          $theFields['about']['divisions'][] = array(
            'dept_name' => $divisionDept->get('title')->getValue()[0]['value'],
            'dept_url' => $divisionDept->toUrl('canonical', ['absolute'=>true])->toString(),
          );
        }
      }
    }
    if($node->hasField('field_public_bodies')) {
      $aboutPublicBodies = $node->get('field_public_bodies')->getValue();
      if($aboutPublicBodies) {
        foreach($aboutPublicBodies as $publicBody) {
          $publicBodyId = $publicBody['target_id'];
          $publicBodyParagraph = Paragraph::load($publicBodyId);
          $publicBodyLink = $publicBodyParagraph->get('field_link');
          $publicBodyValue = $publicBodyLink->getValue();
          $theFields['about']['public_bodies'][] = array(
            'name' => $publicBodyValue[0]['title'],
            'url' => $publicBodyValue[0]['uri'],
          );
        }
      }
    }
    if($node->hasField('field_image')) {
      $aboutLogo = $node->get('field_image');
      if($aboutLogo && $aboutLogo->getValue()) {
        $logoUrl = file_create_url($aboutLogo->entity->field_media_image->entity->getFileUri());
        $theFields['about']['logo_url'] = $logoUrl;
      }
    }
    if($node->hasField('field_call_to_action')) {
      $aboutCta = $node->get('field_call_to_action');
      if($aboutCta && $aboutCta->getValue()) {
        $aboutCtaId = $aboutCta->getValue()[0]['target_id'];
        $aboutCtaParagraph = Paragraph::load($aboutCtaId);
        $ctaTitle = $aboutCtaParagraph->get('field_title')->getValue()[0]['value'];
        $ctaButtonId = $aboutCtaParagraph->get('field_button')->getValue() ? $aboutCtaParagraph->get('field_button')->getValue()[0]['target_id'] : null;
        if($ctaButtonId) {
          $ctaButtonParagraph = Paragraph::load($ctaButtonId);
          $ctaButtonText = $ctaButtonParagraph->get('field_link')->getValue()[0]['title'];
          $ctaButtonUrl = $ctaButtonParagraph->get('field_link')->getValue()[0]['uri'];
          $theFields['about']['cta']['title'] = $ctaTitle;
          $theFields['about']['cta']['button_text'] = $ctaButtonText; 
          $theFields['about']['cta']['url'] = $ctaButtonUrl;
        }
      }
    }
    if($node->hasField('field_social_media')) {
      $aboutSocial = $node->get('field_social_media');
      if($aboutSocial && $aboutSocial->getValue()) {
        $socialMediaLabel = $aboutSocial->getFieldDefinition()->getLabel();
        $theFields['about']['social']['title'] = $socialMediaLabel;
        $aboutSocialId = $aboutSocial->getValue()[0]['target_id'];
        $aboutSocialParagraph = Paragraph::load($aboutSocialId);
        $socialFields = $aboutSocialParagraph->getFields();
        foreach($socialFields as $socialField) {
          if($socialField->getFieldDefinition()->getType() == 'link') {
            $socialFieldKey = $socialField->getFieldDefinition()->getName();
            $socialLabel = $socialField->getFieldDefinition()->getLabel();
            $socialUrl = $aboutSocialParagraph->get($socialFieldKey)->getValue() ? $aboutSocialParagraph->get($socialFieldKey)->getValue()[0]['uri'] : null;
            if($socialUrl) {
              $theFields['about']['social']['values'][] = array(
                'label' => $socialLabel,
                'url' => $socialUrl,
              );
            }
          }
        }
      }
    }
    if($node->hasField('field_people')) {
      $aboutPeople = $node->get('field_people');
      if($aboutPeople && !empty($aboutPeople->getValue())) {
        $peopleId = $aboutPeople->getValue()[0]['target_id'];
        $peopleParagraph = Paragraph::load($peopleId);
        if(!empty($peopleParagraph->get('field_person_2')->getValue())) {
          $people = $peopleParagraph->get('field_person_2')->getValue();
          foreach($people as $person) {
            $personNode = Node::load($person['target_id']);
            $theFields['about']['people'][] = array(
              'first_name' => $personNode->get('field_first_name')->getValue()[0]['value'],
              'last_name' => $personNode->get('field_last_name')->getValue()[0]['value'],
              'title' => $personNode->get('field_title')->getValue()[0]['value'],
              'url' => $personNode->get('field_direct_external_url')->getValue() ? $personNode->get('field_direct_external_url')->getValue()[0]['uri'] : '',
              'photo' => file_create_url($personNode->get('field_photo')->entity->uri->value),
            );
          }
        }
      }
    }
    if($node->hasField('field_req_public_records')) {
      $reqPublicRecords = $node->get('field_req_public_records');
      if($reqPublicRecords && !empty($reqPublicRecords->getValue())) {
        $reqPublicRecordsMethod = strtolower($reqPublicRecords->getValue()[0]['value']);
        $reqPublicRecordsValue = null;
        switch($reqPublicRecordsMethod) {
          case 'link':
            if(!empty($node->get('field_req_public_records_link')->getValue())) {
              $reqPublicRecordsValue = $node->get('field_req_public_records_link')->getValue()[0]['uri'];
            }
            break;
          case 'email':
            if(!empty($node->get('field_req_public_records_email')->getValue())) {
              $reqPublicRecordsValue = $node->get('field_req_public_records_email')->getValue()[0]['value'];
            }
            break;
          case 'phone':
            if(!empty($node->get('field_req_public_records_phone')->getValue())) {
              $reqPublicRecordsValue = $node->get('field_req_public_records_phone')->getValue()[0]['value'];
            }
            break;
          default:
            $reqPublicRecordsValue = null;
        }
        if($reqPublicRecordsMethod && $reqPublicRecordsValue) {
          $theFields['public_records']['method'] = $reqPublicRecordsMethod;
          $theFields['public_records']['value'] = $reqPublicRecordsValue;
        }
      }
    }
  }           
  return $theFields;
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
    $blocks = entity_load_multiple_by_properties('block', array('theme' => $theme, 'region' => $region));
    uasort($blocks, 'Drupal\block\Entity\Block::sort');
    // Capture viewable blocks and their settings to $build
    $build = array();
    foreach ($blocks as $key => $block) {
      if ($block->access('view')) {
        $build[$key] = entity_view($block, 'block');
      }
    }
    // Add build to region
    $variables[$region] = $build;
  }
}
