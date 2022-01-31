<?php

namespace Drupal\sfgov_utilities\ResourceMigration;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\eck\Entity\EckEntity;

class ResourceMigration {
  private $eckResourcesData; // a list of existing and newly created eck resources
  
  private $report;
  private $duplicateReport;

  public function __construct() {
    $this->eckResourcesData = [];
    $this->report = new ResourceMigrationReport();
    $this->duplicateReport = new ResourceMigrationReport();
  }

  private function createResourceEntity(string $title, string $description, string $url) {
    $entity = null;
    $eckData = [
      'entity_type' => 'resource',
      'type' => 'resource',
      'title' => $title,
      'field_description' => $description,
      'field_url' => $url
    ];
    $entity = EckEntity::create($eckData);
    $entity->save();

    $this->eckResourcesData[$url] = [
      'id' => $entity->id(),
      'field_title' => $entity->field_title->value,
      'field_description' => $entity->field_description->value,
      'field_url' => $entity->field_url->uri,
    ];
    return $entity;
  }

  private function getNodesOfType(string $contentType) {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', $contentType)
      ->execute();
    return !empty($nids) ? Node::loadMultiple($nids) : null;
  }

  public function migrateCampaignResources() {
    // campaign resources are structured like this:
    // Additional content (field_contents)
    // --> Campaign resources (paragraph: campaign_resources)
    // ----> Resources (field_resources)
    // ------> Campaign resource section (paragraph: campaign_resource_section)
    // --------> Resources (field_content)
    // ----------> paragraph: resources
    $resourcesToRemove = []; // track resources to remove
    $nodes = $this->getNodesOfType('campaign');
    foreach($nodes as $node) {
      if ($node->hasField('field_contents')) {
        $fieldValues = $node->get('field_contents')->getValue();
        if (!empty($fieldValues)) {
          foreach ($fieldValues as $fieldValue) {
            $targetId = $fieldValue['target_id'];
            $campaignResourcesParagraph = Paragraph::load($targetId);
            $campaignResourcesParagraphType = $campaignResourcesParagraph->getType();
            if ($campaignResourcesParagraphType == 'campaign_resources') {
              // now check if campaign resources paragraph has campaign resource section
              $campaignResourceSectionValues = $campaignResourcesParagraph->get('field_resources')->getValue();
              if (!empty($campaignResourceSectionValues)) {
                foreach ($campaignResourceSectionValues as $campaignResourceSectionValue) {
                  $campaignResourceSectionId = $campaignResourceSectionValue['target_id'];
                  $campaignResourceSectionParagraph = Paragraph::load($campaignResourceSectionId);
                  $resourcesValues = $campaignResourceSectionParagraph->get('field_content')->getValue();
                  if (!empty($resourcesValues)) {
                    $resourcesToRemove[$campaignResourceSectionId] = [];
                    foreach ($resourcesValues as $resourcesValue) {
                      $resourceId = $resourcesValue['target_id'];
                      $resourcesParagraph = Paragraph::load($resourceId);
                      if ($resourcesParagraph->gettype() == 'resources') { // this is the old thing                      
                        $title = $resourcesParagraph->field_title->value;
                        $description = $resourcesParagraph->field_description->value;
                        $uri = $resourcesParagraph->field_link->uri;
                        $isEntityRef = strpos($uri, 'entity:');
  
                        $resource = [
                          'resource_field_link' => $uri,
                          'resource_id' => $resourceId,
                          'resource_type' => $resourcesParagraph->gettype(),
                          'resource_field_title' => $title,
                          'resource_field_description' => $description,
                          'node_id' => $node->id(),
                          'node_content_type' => $node->getType(),
                          'node_content_title' => $node->getTitle(),
                        ];

                        $this->duplicateReport->addItem($resource, $uri);

                        $this->report->addItem($resource);

                        // $campaignResourceSection['resources'][] = $resource;
  
                        // // if link uri contains "entity:"
                        // //  it's an internal reference, create sf.gov link paragraph
                        // // else it's external
                        // //  create eck resource entity 
                        // //  create new external link paragraph entity (machine name: resource_entity)
                        // //  attach eck entity to paragraph entity
                        // //  attach paragraph to campaign resource section
                        // if($isEntityRef !== false) {
                        //   echo "create sf.gov link paragraph\n";
                        // } else {
                        //   echo "create resource entity\n";
                        //   echo "add resource entity to " . $campaignResourceSectionParagraph->field_title->value . "\n";
  
                        //   // create eck entity external link, or get an existing one
                        //   $externalLinkEntity = createResourceEntity($title, $description, $uri, $eckResourcesData);
  
                        //   // create paragraph type resource_entity
                        //   $externalLinkParagraph = Paragraph::create([
                        //     "type" => "resource_entity"
                        //   ]);
  
                        //   // attach eck entity to paragraph resource_entity
                        //   $externalLinkParagraph->field_resource = $externalLinkEntity;
  
                        //   // attach paragraph resource_entity to campaign resource section
                        //   $campaignResourceSectionParagraph->field_content[] = $externalLinkParagraph;
                        //   $campaignResourceSectionParagraph->save();
                        // }
  
                        // $resourcesParagraph->field_title->value = $title . " (delete)";
                        // $resourcesParagraph->save();
  
                        $resourcesToRemove[$campaignResourceSectionId][] = $resourcesParagraph->id();
                      }
                      // else { // this is the new thing
                      //   $resource = [
                      //     'r_id' => $resourceId,
                      //     'resource_type' => $resourcesParagraph->gettype(),
                      //     'entity_id' => $resourcesParagraph->get('field_resource')->getValue()[0]['target_id'],
                      //   ];
                      //   $campaignResourceSection['resources'][] = $resource;
                      // }
                    }
                    // $campaignResource['campaign_resource_section'][] = $campaignResourceSection;
                  }
                }
              }
              // $nodeWithResource['campaign_resources'][] = $campaignResource;
            }
            $campaignResourcesParagraph->save();
          }
        }
      }
      // $nodesWithResources[] = $nodeWithResource;
    }
  }

  public function getReport($json = FALSE) {
    return $this->report->getReport($json);
  }

  public function getDuplicateReport() {
    return $this->duplicateReport->getReport();
  }

}