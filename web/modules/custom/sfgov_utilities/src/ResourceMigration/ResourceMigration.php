<?php

namespace Drupal\sfgov_utilities\ResourceMigration;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\eck\Entity\EckEntity;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

class ResourceMigration {
  private $eckResourcesData; // a list of existing and newly created eck resources
  
  private $report;
  private $duplicateReport; // a report of duplicate resources (based on urls)
  private $nodeReport; // a report of which nodes contain how many resources

  public function __construct() {
    $this->eckResourcesData = [];
    $this->report = new ResourceMigrationReport();
    $this->duplicateReport = new ResourceMigrationReport();
    $this->nodeReport = new ResourceMigrationReport();
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

  private function getNodes(string $type) {
    $nids = \Drupal::entityQuery('node')
    ->currentRevision()
    ->condition('status', TRUE)
    ->condition('type', $type)
    ->execute();
    return $nids;
  }

  // about
  // --> field_about_resources
  // ----> paragraph: tile section (other_info_card)
  // ------> field_resources
  public function migrateAboutResources() {
    $nids = $this->getNodes('about');

    foreach($nids as $nid) {
      $node = Node::load($nid);
      $removes = [];
      $aboutResources = $node->get('field_about_resources')->getValue();
      if(!empty($aboutResources)) {
        foreach($aboutResources as $aboutResource) {
          $aboutResourcesParagraph = Paragraph::load($aboutResource['target_id']);
          $resources = $aboutResourcesParagraph->get('field_resources')->getValue();
          if(!empty($resources)) {
            foreach($resources as $resource) {
              $resourceParagraph = Paragraph::load($resource['target_id']);
              if(!empty($resourceParagraph)) {
                if ($resourceParagraph->getType() == 'resources') {
                  $this->migrateResource($node, $resourceParagraph, $aboutResourcesParagraph, 'field_resources');
                  $removes[] = $resourceParagraph->id();
                }
              }
            }
          }
          for($i=0; $i<count($removes); $i++) {
            $removeId = $removes[$i];
            $resources = $aboutResourcesParagraph->get('field_resources')->getValue();
            for($j=0; $j<count($resources); $j++) {
              $targetId = $resources[$j]['target_id'];
              if($removeId == $targetId) {
                $aboutResourcesParagraph->get('field_resources')->removeItem($j);
                $aboutResourcesParagraph->save();
              }
            }
          }
          $node->save();
        }
      }
    }
  }

  // departments
  // --> field_resources
  //
  // topics
  // --> field_resources
  public function migrateTopicsAndDepartments() {
    $nids = array_merge($this->getNodes('department'), $this->getNodes('topic'));
    foreach($nids as $nid) {
      $node = Node::load($nid);
      $removes = [];
      $resources = $node->get('field_resources')->getValue();
      if(!empty($resources)) {
        foreach($resources as $resource) {
          $resourceParagraph = Paragraph::load($resource['target_id']);
          if ($resourceParagraph->getType() == 'resources') {
            $this->migrateResource($node, $resourceParagraph, $node, 'field_resources');
            $removes[] = $resourceParagraph->id();
          }
        }
        echo "\n";
      }
      for($i=0; $i<count($removes); $i++) {
        $removeId = $removes[$i];
        $resources = $node->get('field_resources')->getValue();
        for($j=0; $j<count($resources); $j++) {
          $targetId = $resources[$j]['target_id'];
          if($removeId == $targetId) {
            $node->get('field_resources')->removeItem($j);
          }
        }
      }
      $node->save();
    }
  }

  public function migrateResource($node, $resourceParagraph, $containingParent, $containingParentFieldName) {
    $containingNode = $node;
    $immediateParent = $containingParent;
    $immediateParentFieldName = $containingParentFieldName;
    $reportOnly = false;
    
    $contentType = $containingNode->getType();
    $title = $resourceParagraph->field_title->value ?? '';
    $description = $resourceParagraph->field_description->value ?? '';
    $uri = $resourceParagraph->field_link->uri ?? '';
    $isEntityRef = strpos($uri, 'entity:');

    // for reporting
    $resource = [
      'resource_field_link' => $uri,
      'resource_id' => $resourceParagraph->id(),
      'resource_type' => $resourceParagraph->getType(),
      'resource_field_title' => $title,
      'resource_field_description' => $description,
      'node_id' => $containingNode->id(),
      'node_content_type' => $contentType,
      'node_title' => $containingNode->getTitle(),
      'node_author' => User::load($containingNode->getOwner()->id())->getDisplayName(),
      'last_updated' => date("m/d/Y", $containingNode->changed->value)
    ];
    $this->report->addItem($resource);
    $this->nodeReport->addItem($resource, $containingNode->id());
    if(!empty($uri)) {
      $this->duplicateReport->addItem($resource, $uri);
    }

    if(!$reportOnly) {
      if(!empty($immediateParentFieldName)) {
        $newResourceParagraph = null;
        if($isEntityRef !== false) {
          $params = Url::fromUri($uri)->getRouteParameters();
          $sfgovLinkParagraph = Paragraph::create([
            "type" => "resource_node"
          ]);
          $sfgovLinkParagraph->field_node->target_id = $params['node'];
          $sfgovLinkParagraph->save();

          $newResourceParagraph = $sfgovLinkParagraph;
        } else {
          // create eck entity external link, or get an existing one
          $externalLinkEntity = $this->createResourceEntity($title, $description, $uri);

          // create paragraph type resource_entity
          $externalLinkParagraph = Paragraph::create([
            "type" => "resource_entity"
          ]);

          // attach eck entity to paragraph resource_entity
          $externalLinkParagraph->field_resource = $externalLinkEntity;

          $newResourceParagraph = $externalLinkParagraph;
        }

        // attach new resource paragraph to immediate parent
        $immediateParent->get($immediateParentFieldName)[] = $newResourceParagraph;
        $immediateParent->save();
        $containingNode->save();
      }
    }
  }


  // 
  // campaign:
  // --> Additional content (field_contents)
  // ----> Campaign resources (paragraph: campaign_resources)
  // ------> Resources (field_resources)
  // --------> Campaign resource section (paragraph: campaign_resource_section)
  // ----------> Resources (field_content)
  //
  // resource collections
  // --> field_content_bottom
  // ----> paragraph: Section
  // ------> section content (field_content)
  // --------> resource_section
  // ----------> resource_subsection
  // ------------> field_resources

  public function getReport() {
    return $this->report->getReport();
  }

  public function getDuplicateReport() {
    $records = $this->duplicateReport->getReport();
    $dupes = [];
    // flatten dupes
    foreach($records as $key => $value) {
      $items = $value;
      $numItems = count($items);
      if($numItems > 1) {
        for($i = 0; $i < $numItems; $i++) {
          $dupes[] = $items[$i];
        }
      }
    }
    echo json_encode($dupes, JSON_UNESCAPED_SLASHES);
  }

  public function getNodeReport() {
    $records = $this->nodeReport->getReport();
    $nodesWithResourceCount = [];
    foreach($records as $key => $value) {
      $items = $value;
      $numItems = count($items);
      $nodesWithResourceCount[] = [
        "nid" => $key,
        "content_type" => $items[0]['node_content_type'],
        "node_title" => $items[0]['node_title'],
        "node_author" => $items[0]['node_author'],
        "resource_count" => $numItems
      ];
    }
    print_r($nodesWithResourceCount);
  }
}