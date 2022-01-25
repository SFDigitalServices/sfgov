<?php

require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity;
use Drupal\eck\Entity\EckEntity;
use Drupal\Core\Link;
use Drupal\Core\Url;

function createResourceEntity($title, $description, $url) {
  $link = Link::fromTextAndUrl('some url', Url::fromUri('https://some-uri'));
  $eckData = [
    'entity_type' => 'resource',
    'type' => 'resource',
    'title' => $title . ' (entity title from code ' . time() . ')',
    'field_description' => $description,
    'field_url' => $url
  ];

  $entity = EckEntity::create($eckData);
  $entity->save();

  // $entity = EckEntity::create($entityType, ['type' => $entityBundle]);
  // $wrapper = entity_metadata_wrapper($entityType, $entity);
  // $wrapper->field_description->set('entity description from code ' . time());
  // $wrapper->field_url->set('field_url', $link);
}

// createEntity('resource', 'resource');

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
  if($node->id() == 3129) {
    $title = $node->getTitle();
    // echo "\e[96m" . $node->gettype() . ": " . $title . " (". $node->id() . ")\n";
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
            // echo "\e[32m  target_id:" . $targetId . ", paragraph type:" . $campaignResourcesParagraphType . ", title: " . $campaignResourcesParagraph->field_title->value . "\n";
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
                // echo "\e[93m    target_id:" . $campaignResourceSectionId . ", paragraph type:" . $campaignResourceSectionParagraph->getType() . ", title: " . $campaignResourceSectionParagraph->field_title->value . "\n";
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
                      // echo "\e[95m";
                      // echo "      resource id: " . $resourceId . "\n";
                      // echo "      resource_type: " . $resourcesParagraph->gettype() . "\n";
                      // echo "      field_title: " . $resourcesParagraph->field_title->value . "\n";
                      // echo "      field_description: " . $resourcesParagraph->field_description->value . "\n";
                      // echo "      field_link: " . $resourcesParagraph->field_link->uri . "\n\n";
                      
                      $title = $resourcesParagraph->field_title->value;
                      $description = $resourcesParagraph->field_description->value;
                      $uri = $resourcesParagraph->field_link->uri;
                      $isEntityRef = strpos($uri, 'entity:') >= 0 ? true : false;

                      $resource = [
                        'id' => $resourceId,
                        'resource_type' => $resourcesParagraph->gettype(),
                        'field_title' => $title,
                        'field_description' => $description,
                        'field_link' => $uri
                      ];
                      $campaignResourceSection['resources'][] = $resource;

                      // if link uri contains "entity:", it's an internal reference
                      // else it's external
                      // create new resource entity accordingly
                      if($isEntityRef) {

                      } else {
                        createResourceEntity($title, $description, $uri);
                      }


                    } else { // this is the new thing
                      // echo "\e[95m";
                      // echo "      resource id: " . $resourceId . "\n";
                      // echo "      resource_type: " . $resourcesParagraph->gettype() . "\n";
                      // echo "\n";
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
}

$json = json_encode($nodesWithResources);

// echo "\e[97m";
// echo "\n";

$campaignsWithResources = 0;
foreach($nodesWithResources as $nodeWithResource) {
  $campaignResources = $nodeWithResource['campaign_resources'];
  if(!empty($campaignResources)) {
    foreach($campaignResources as $campaignResource) {
      echo "\e[96m";
      echo "campaign: " . $nodeWithResource['title'] . " (" . $nodeWithResource['id'] . ")\n";
      echo "\e[32m";
      echo "  campaign_resource title: " . $campaignResource['title'] . "\n";
      $campaignResourceSections = $campaignResource['campaign_resource_section'];
      if(!empty($campaignResourceSection)) {
        foreach($campaignResourceSections as $campaignResourceSection) {
          echo "\e[93m";
          echo "      ($campaignResourceSectionId) campaign_resource_section title: " . $campaignResourceSection['title'] . "\n";
          $resources = $campaignResourceSection['resources'];
          if(!empty($resources)) {
            foreach($resources as $resource) {
              echo "\e[95m";
              echo "        resource:\n";
              echo "          field_title:\e[37m" . $resource['field_title'] . "\n";
              echo "          \e[95mfield_description:\e[37m" . $resource['field_description'] . "\n";
              echo "          \e[95mfield_link:\e[37m" . $resource['field_link'] . "\n";
            }
          }
        }
      }
    }
    echo "\n";
    $campaignsWithResources++;
  }
}

echo "\n";
echo "\e[97mcampaigns with resources: " . $campaignsWithResources . "\n";