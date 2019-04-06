<?php

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\eck\Entity\EckEntityBundle;
use Drupal\Core\Entity;

// $module_handler = Drupal::service('module_handler');
// $devel_path = $module_handler->getModule('devel')->getPath();
// require_once($devel_path.'/kint/kint/Kint.class.php');
// Kint::$maxLevels = 4;

function sfgovpl_preprocess_node(&$variables) {
  if(!empty($variables['node'])) {
    $content_type = $variables['node']->getType();
    $tags = [];
    if($content_type == 'information_page') {
      $tags = info_page_tags();
      $variables['sfgov_tags'] = $tags;
    }
    if($content_type == 'department') {
      $variables['dept_fields'] = dept_page_fields();
    }
  }
}

// expose the information page "part of" tags for template
function info_page_tags() {
  $node = \Drupal::request()->attributes->get('node');
  $tx_tags_exposed = [];
  if($node && $node->hasField('field_transactions')) {
    $tx_nodes = $node->get('field_transactions')->referencedEntities();
    foreach($tx_nodes as $tx_node) {
      $tx_tags_exposed[] = [
        'name' => $tx_node->getTitle(),
        'url' => $tx_node->toUrl()->toString(),
      ];
    }
  }
  return $tx_tags_exposed;
}

function dept_page_fields() {
  $node = \Drupal::request()->attributes->get('node');
  $theFields = array(
    'featured_items' => array(),
    'services' => array(),
  );
  if($node) {
    if($node->hasField('field_featured_items')) {
      // $paragraphFeaturedItemsId = $node->get('field_featured_items')->getValue()[0]['target_id'];
      // $featuredItems = Paragraph::load($paragraphFeaturedItemsId);
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
      foreach($deptServices as $deptService) {
        $deptServiceSectionId = $deptService->getValue()['target_id'];
        $deptServiceSectionLoaded = Paragraph::load($deptServiceSectionId);
        $deptServiceSectionTitle = $deptServiceSectionLoaded->get('field_dept_service_section_title')->getValue()[0]['value'];
        //'field_dept_service_sect_services'
        // load the paragraph and get the transactions
        $deptTransactions = $deptServiceSectionLoaded->get('field_dept_service_sect_services')->getValue();
        $txs = array();
        for($i=0; $i<count($deptTransactions); $i++) {
          // Kint::dump($deptTransactions[$i]['target_id']);
          $txId = $deptTransactions[$i]['target_id'];
          $tx = Node::load($txId);
          $txs[] = array(
            'tx_title' => $tx->get('title')->getValue()[0]['value'],
            'tx_description' => $tx->get('field_description')->getValue()[0]['value'],
          );
        }
        // Kint::dump($deptTransactions);
        $theFields['services']['values'][] = array(
          'title' => $deptServiceSectionTitle,
          'transactions' => $txs,
        );
      }
      if($node->hasField('field_spotlight')) {
        $deptSpotlight = $node->get('field_spotlight');
        if(!empty($deptSpotlight) && !empty($deptSpotlight->getValue())) {
          // Kint::dump($deptSpotlight->getValue());
          $deptSpotlightParagraphId = $deptSpotlight->getValue()[0]['target_id'];
          $deptSpotlightParagraph = Paragraph::load($deptSpotlightParagraphId);
          $spotlightTitle = $deptSpotlightParagraph->get('field_title')->getValue()[0]['value'];
          $spotlightDescription = $deptSpotlightParagraph->get('field_description')->getValue()[0]['value'];
          $spotlightButtonId = $deptSpotlightParagraph->get('field_spotlight_button')->getValue()[0]['target_id'];
          $spotlightButtonParagraph = Paragraph::load($spotlightButtonId);
          $spotlightButtonTitle = $spotlightButtonParagraph->get('field_link')->getValue()[0]['title'];
          $spotlightButtonUri = $spotlightButtonParagraph->get('field_link')->getValue()[0]['uri'];
          $spotlightImage = $deptSpotlightParagraph->get('field_spotlight_image');
          // Kint::dump($spotlightImage->entity->url());
          $theFields['spotlight']['title'] = $spotlightTitle;
          $theFields['spotlight']['description'] = $spotlightDescription;
          $theFields['spotlight']['button_text'] = $spotlightButtonTitle;
          $theFields['spotlight']['button_uri'] = $spotlightButtonUri;
          $theFields['spotlight']['image_url'] = $spotlightImage->entity->url();
        }
      }
      if($node->hasField('field_resources')) {
        $resources = $node->get('field_resources');
        $resourcesLabel = $resources->getFieldDefinition()->getLabel();
        // Kint::dump($resourcesLabel);
        $theFields['resources']['label'] = $resourcesLabel;
        foreach($resources as $resource) {
          $resourceParagraphId = $resource->getValue()['target_id'];
          // Kint::dump($resourceParagraphId);
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
        $deptUrl = $node->get('field_url')->getValue()[0]['uri'];
        $theFields['dept_url'] = $deptUrl;
      }
      if($node->hasField('field_address')) {
        $addressId = $node->get('field_address')->getValue()[0]['target_id'];
        $addressEntity = \Drupal::entityTypeManager()->getStorage('location')->load($addressId);
        $addressValues = $addressEntity->get('field_address')->getValue()[0];
        $theFields['address']['line1'] = $addressValues['address_line1'];
        $theFields['address']['line2'] = $addressValues['address_line2'];
        $theFields['address']['country_code'] = $addressValues['country_code'];
        $theFields['address']['city'] = $addressValues['locality'];
        $theFields['address']['state'] = $addressValues['administrative_area'];
        $theFields['address']['zip'] = $addressValues['postal_code'];
        $theFields['address']['organization'] = $addressValues['organization'];
        $hours = $addressEntity->get('field_operating_hours')->getValue();
        // Kint::dump($addressValues);
        usort($hours, function($item1, $item2) {
          return $item1['day'] <=> $item2['day'];
        });
        // Kint::dump($hours);
        foreach($hours as $hour) {
          $theFields['address']['hours'][] = array(
            'day' => jddayofweek($hour['day'], 2),
            'start' => date("g:i a", strtotime($hour['starthours'])),
            'end' => date("g:i a", strtotime($hour['endhours'])),
            'comment' => $hour['comment'],
          );
        }
      }
      if($node->hasField('field_phone_numbers')) {
        $phoneNumbers = $node->get('field_phone_numbers');
        if($phoneNumbers->getValue()) {
          foreach($phoneNumbers->getValue() as $phoneNumber) {
            $phoneNumberId = $phoneNumber['target_id'];
            $phoneParagraph = Paragraph::load($phoneNumberId);
            // Kint::dump($phoneParagraph->get('field_tel')->getValue()[0]['value']);
            $number = $phoneParagraph->get('field_tel')->getValue()[0]['value'];
            $number = preg_replace("/[^\d]/","",$number); // remove non-digit characters
            $length = strlen($number);
            if($length == 10) {
              $number = preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $number);
            }
            $theFields['phone_numbers'][] = array(
              'number' => $number,
              'details' => $phoneParagraph->get('field_text')->getValue()[0]['value'],
              'owner' => $phoneParagraph->get('field_owner')->getValue()[0]['value'],
            );
          }
        }
      }
      if($node->hasField('field_email')) {
        $emailField = $node->get('field_email');
        if($emailField->getValue()) {
          $emailId = $emailField->getValue()[0]['target_id'];
          $emailParagraph = Paragraph::load($emailId);
          $email = $emailParagraph->get('field_email')->getValue()[0]['value'];
          $theFields['email'] = $email;
        }
      }
    }
  }           
  // Kint::dump($theFields);
  return $theFields;
}