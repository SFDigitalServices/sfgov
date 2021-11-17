<?php

require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity;

$users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail'=>'webmaster@sfgov.org']);
$user = reset($users);
$user_id = $user->id();

$nids = \Drupal::entityQuery('node')->condition('type','campaign')->execute();
$nodes = Node::loadMultiple($nids);

// campaign nodes with resources
$campaignsWithResources = 0;
$campaignsWithOldResources = 0;

$nodesWithResources = [];

// campaign nodes
foreach($nodes as $node) {
  $title = $node->getTitle();
  echo "\e[96m" . $node->gettype() . ": " . $title . " (". $node->id() . ")\n";
  // check for field
  $nodeWithResource = [
    'id' => $node->id(),
    'title' => $node->getTitle(),
    'content_type' => $node->gettype(),
    'campaign_resources' => []
  ];
  if ($node->hasField('field_contents')) {
    $fieldValues = $node->get('field_contents')->getValue();
    if (!empty($fieldValues)) {
      foreach ($fieldValues as $fieldValue) {
        $targetId = $fieldValue['target_id'];
        $campaignResourcesParagraph = Paragraph::load($targetId);
        $campaignResourcesParagraphType = $campaignResourcesParagraph->getType();
        if ($campaignResourcesParagraphType == 'campaign_resources') {
          echo "\e[32m  target_id:" . $targetId . ", paragraph type:" . $campaignResourcesParagraphType . ", title: " . $campaignResourcesParagraph->field_title->value . "\n";
          $campaignResource = [
            'id' => $targetId,
            'title' => $campaignResourcesParagraph->field_title->value,
            'campaign_resource_section' => [],
          ];
          // now check if campaign resources paragraph has campaign resource section
          $campaignResourceSectionValues = $campaignResourcesParagraph->get('field_resources')->getValue();
          if (!empty($campaignResourceSectionValues)) {
            foreach ($campaignResourceSectionValues as $campaignResourceSectionValue) {
              $campaignResourceSectionId = $campaignResourceSectionValue['target_id'];
              $campaignResourceSectionParagraph = Paragraph::load($campaignResourceSectionId);
              echo "\e[93m    target_id:" . $campaignResourceSectionId . ", paragraph type:" . $campaignResourceSectionParagraph->getType() . ", title: " . $campaignResourceSectionParagraph->field_title->value . "\n";
              $campaignResourceSection = [
                'id' => $campaignResourceSectionId,
                'title' => $campaignResourceSectionParagraph->field_title->value,
                'resources' => [],
              ];
              $resourcesValues = $campaignResourceSectionParagraph->get('field_content')->getValue();
              if (!empty($resourcesValues)) {
                foreach ($resourcesValues as $resourcesValue) {
                  $resourceId = $resourcesValue['target_id'];
                  $resourcesParagraph = Paragraph::load($resourceId);
                  if ($resourcesParagraph->gettype() == 'resources') { // this is the old thing
                    echo "\e[95m";
                    echo "      resource id: " . $resourceId . "\n";
                    echo "      resource_type: " . $resourcesParagraph->gettype() . "\n";
                    echo "      field_title: " . $resourcesParagraph->field_title->value . "\n";
                    echo "      field_description: " . $resourcesParagraph->field_description->value . "\n";
                    echo "      field_link: " . $resourcesParagraph->field_link->uri . "\n\n";

                    $resource = [
                      'id' => $resourceId,
                      'resource_type' => $resourcesParagraph->gettype(),
                      'field_title' => $resourcesParagraph->field_title->value,
                      'field_description' => $resourcesParagraph->field_description->value,
                      'field_link' => $resourcesParagraph->field_link->uri,
                    ];
                    $campaignResourceSection['resources'][] = $resource;
                  } else { // this is the new thing
                    echo "\e[95m";
                    echo "      resource id: " . $resourceId . "\n";
                    echo "      resource_type: " . $resourcesParagraph->gettype() . "\n";
                    echo "\n";
                  }
                }
                $campaignResource['campaign_resource_section'][] = $campaignResourceSection;
              } 
            }
          }
          $nodeWithResource['campaign_resources'][] = $campaignResource;
        }
      }
    }
  }
  $nodesWithResources[] = $nodeWithResource;
}

$json = json_encode($nodesWithResources);

// echo "\e[97m campaigns with resources: " . $campaignsWithResources . "\n";
echo "\e[97m";
// print_r($nodesWithResources);
// echo json_encode($nodesWithResources);
echo "\n";

// $campaignsWithResources = 0;
// foreach($nodesWithResources as $nid => $item) {
//   $campaignResources = $nodesWithResources[$nid]['campaign_resources'];
//   if(!empty($campaignResources)) {
//     echo $nid . ":" . $nodesWithResources[$nid]['title'] . "\n";
//     echo "  campaign resource title: " . $campaignResources['title'] . "\n";

//     $campaignsWithResources++;
//   }
// }

// echo "\n";
// echo "\e[97mcampaigns with resources: " . $campaignsWithResources . "\n";
