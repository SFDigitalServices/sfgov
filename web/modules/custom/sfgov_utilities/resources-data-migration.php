<?php

require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity;
use Drupal\eck\Entity\EckEntity;
use Drupal\eck\Entity\EckEntityType;
use Drupal\eck\Entity\EckEntityBundle;
use Drupal\Core\Link;
use Drupal\Core\Url;

$eckResourcesData = [];
$eckIds = \Drupal::entityQuery('resource')->execute();
$eckResources = \Drupal::entityTypeManager()->getStorage('resource')->loadMultiple($eckIds);
foreach($eckResources as $eckResource) {
  $eckResourcesData[$eckResource->field_url->uri] = [
    'id' => $eckResource->id(),
    'field_title' => $eckResource->field_title->value,
    'field_description' => $eckResource->field_description->value,
    'field_url' => $eckResource->field_url->uri,
  ];
}

print_r($eckResourcesData);
// createResourceEntity('some title', 'some description', 'https://sf.gov2', $eckResourcesData);

function createResourceEntity($title, $description, $url, &$eckResourcesData) {
  // what determines uniqueness of resource entity? currently assuming url
  // if eck entity resource with url already exists, just return that entity
  // else create a new entity, add it to the list
  $entity = null;
  if(!empty($eckResourcesData[$url])) {
    $entity = \Drupal::entityTypeManager()->getStorage('resource')->load($eckResourcesData[$url]['id']);
  } else {
    $eckData = [
      'entity_type' => 'resource',
      'type' => 'resource',
      'title' => $title . ' (entity title from code ' . time() . ')',
      'field_description' => $description,
      'field_url' => $url
    ];
    $entity = EckEntity::create($eckData);
    $entity->save();

    $eckResourcesData[$url] = [
      'id' => $entity->id(),
      'field_title' => $entity->field_title->value,
      'field_description' => $entity->field_description->value,
      'field_url' => $entity->field_url->uri,
    ];
  }

  return $entity;
}

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
  if($node->id() == 3135) {
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
                      echo "$uri:";
                      $isEntityRef = strpos($uri, 'entity:');

                      $resource = [
                        'id' => $resourceId,
                        'resource_type' => $resourcesParagraph->gettype(),
                        'field_title' => $title,
                        'field_description' => $description,
                        'field_link' => $uri
                      ];
                      $campaignResourceSection['resources'][] = $resource;

                      // if link uri contains "entity:"
                      //  it's an internal reference, create sf.gov link paragraph
                      // else it's external
                      //  create eck resource entity 
                      //  create new external link paragraph entity (machine name: resource_entity)
                      //  attach eck entity to paragraph entity
                      //  attach paragraph to campaign resource section
                      if($isEntityRef !== false) {
                        echo "create sf.gov link paragraph\n";
                      } else {
                        echo "create resource entity\n";
                        echo "add resource entity to " . $campaignResourceSectionParagraph->field_title->value . "\n";

                        // create eck entity external link, or get an existing one
                        $externalLinkEntity = createResourceEntity($title, $description, $uri, $eckResourcesData);

                        // create paragraph type resource_entity
                        $externalLinkParagraph = Paragraph::create([
                          "type" => "resource_entity"
                        ]);

                        // attach eck entity to paragraph resource_entity
                        $externalLinkParagraph->field_resource = $externalLinkEntity;

                        // attach paragraph resource_entity to campaign resource section
                        $campaignResourceSectionParagraph->field_content[] = $externalLinkParagraph;
                        $campaignResourceSectionParagraph->save();
                      }
                    } else { // this is the new thing
                      $resource = [
                        'id' => $resourceId,
                        'resource_type' => $resourcesParagraph->gettype(),
                        'entity_id' => $resourcesParagraph->get('field_resource')->getValue()[0]['target_id'],
                      ];
                      $campaignResourceSection['resources'][] = $resource;
                    }
                  }
                  $campaignResource['campaign_resource_section'][] = $campaignResourceSection;
                }
              }
            }
            $nodeWithResource['campaign_resources'][] = $campaignResource;
          }
          $campaignResourcesParagraph->save();
        }
      }
    }
    $nodesWithResources[] = $nodeWithResource;
    $node->save();
  }
}

$json = json_encode($nodesWithResources);

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
              echo "          \e[95mid:\e[37m" . $resource['id'] . "\n";
              echo "          \e[95mtype:\e[37m" . $resource['resource_type'] . "\n";
              echo "          \e[95mentity_id:\e[37m" . $resource['entity_id'] . "\n";
              echo "          \e[95mfield_title:\e[37m" . $resource['field_title'] . "\n";
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