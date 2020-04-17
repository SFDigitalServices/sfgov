<?php

use Drupal\paragraphs\Entity\Paragraph;

function sfgovpl_preprocess_html(&$variables) {

  // for metadata
  if(!empty($variables['node_type'])) {
    $nodeType = $variables['node_type'];
    if($nodeType == 'department') {
      $node = \Drupal::request()->attributes->get('node');

      $metaDeptPhone = [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'departmentPhone',
          'content' => '',
        ]
      ];
      $metaDeptAddress = [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'departmentAddress',
          'content' => '',
        ]
      ];

      if(count($node->get('field_phone_numbers')->getValue()) > 0) {
        $paragraphPhoneId = $node->get('field_phone_numbers')->getValue()[0]['target_id'];
        $phone = Paragraph::load($paragraphPhoneId);
        $metaDeptPhone['#attributes']['content'] = $phone->field_tel->value;
      }

      if(count($node->get('field_address')->getValue()) > 0) {
        $paragraphAddressId = $node->get('field_address')->getValue()[0]['target_id'];
        $paragraphAddress = Paragraph::load($paragraphAddressId);
        $address = $node->get('field_address')->referencedEntities()[0];
        $metaDeptAddress['#attributes']['content'] = $address->field_address->address_line1;
      }

      $variables['page']['#attached']['html_head'][] = [$metaDeptPhone, 'departmentPhone'];
      $variables['page']['#attached']['html_head'][] = [$metaDeptAddress, 'departmentAddress'];
    }

    if($nodeType == 'transaction') {
      $txNode = \Drupal::request()->attributes->get('node');
      $relatedDepts = $txNode->get('field_departments')->getValue();
      $transaction = [
        'related_depts' => [],
      ];
      foreach($relatedDepts as $relatedDept) {
        $relatedDeptId = $relatedDept['target_id'];
        $relatedDeptNode = \Drupal\node\Entity\Node::load($relatedDeptId);
        $relatedDeptInfo = [
          'id' => $relatedDeptId,
          'title' => $relatedDeptNode ? $relatedDeptNode->getTitle() : '',
        ];
        $transaction['related_depts'][] = $relatedDeptInfo;
      }
      $metaTx = [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'transaction',
          'content' => json_encode($transaction, JSON_HEX_QUOT),
        ],
      ];

      $metaTxRelatedDept = [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'transactionRelatedDept',
          'content' => '',
        ]
      ];

      if(count($relatedDepts) > 0) {
        $oneRelatedDept = \Drupal\Node\Entity\Node::load($relatedDepts[0]['target_id']);
        $metaTxRelatedDept['#attributes']['content'] = $oneRelatedDept ? $oneRelatedDept->getTitle() : '';
      }
      $variables['page']['#attached']['html_head'][] = [$metaTx, 'transaction'];
      $variables['page']['#attached']['html_head'][] = [$metaTxRelatedDept, 'transactionRelatedDept'];
    }
  }

  // for translations
  $node = \Drupal::routeMatch()->getParameter('node');
  $theNode = null;
  $nodeInfo = [];
  $nodeInfo['current_language'] = \Drupal::languageManager()->getCurrentLanguage()->getId();
  if ($node instanceof \Drupal\node\NodeInterface) {
    $theNode = node_load($node->id());
    $nodeInfo['nid'] = $theNode->id();
    $nodeInfo['nurl'] = $theNode->url();
    $langs = $theNode->getTranslationLanguages();
    $nodeInfo['translations'] = [];
    foreach($langs as $lang) {
      $langId = $lang->getId();
      $nodeInfo['translations'][] = [
        'lang' => $langId,
        'turl' => $theNode->getTranslation($langId)->url(),
      ];
    }
  }
  $variables['page']['#attached']['drupalSettings']['sfgov_translations']['node'] = $nodeInfo;
}
