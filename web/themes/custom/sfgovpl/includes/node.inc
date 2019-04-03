<?php

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;

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
    }
  }           
  // Kint::dump($theFields);
  return $theFields;
}