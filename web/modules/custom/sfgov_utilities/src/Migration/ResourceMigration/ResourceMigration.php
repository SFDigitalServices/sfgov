<?php

namespace Drupal\sfgov_utilities\Migration\ResourceMigration;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\eck\Entity\EckEntity;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

class ResourceMigration {
  private $eckResourcesData; // a list of existing and newly created eck resources
  
  private $report;
  private $duplicateReport; // a report of duplicate resources (based on urls)
  private $nodeReport; // a report of which nodes contain how many
  private $validationReport;
  private $dryRun;

  public function __construct() {
    $this->eckResourcesData = [];
    $this->report = new ResourceMigrationReport();
    $this->duplicateReport = new ResourceMigrationReport();
    $this->nodeReport = new ResourceMigrationReport();
    $this->validationReport = new ResourceMigrationReport();
    $dryRun = false;
  }

  public function setDryRun($dryRun) {
    $this->dryRun = $dryRun;
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
    ->condition('status', FALSE) // drafts only
    ->condition('type', $type)
    ->execute();
    return $nids;
  }

  private function removeOldResources($removes, $parent, $field_name) {
    if($this->dryRun == false) {
      for($i=0; $i<count($removes); $i++) {
        $removeId = $removes[$i];
        $resources = $parent->get($field_name)->getValue();
        for($j=0; $j<count($resources); $j++) {
          $targetId = $resources[$j]['target_id'];
          if($removeId == $targetId) {
            $parent->get($field_name)->removeItem($j);
          }
        }
      }
      $parent->save();
    }
  }

  // about
  // --> field_about_resources
  // ----> paragraph: tile section (other_info_card)
  // ------> field_resources
  public function migrateAboutAndPublicBodyResources() {
    $nids = array_merge($this->getNodes('about'), $this->getNodes('public_body'));
    foreach($nids as $nid) {
      $node = Node::load($nid);
      $removes = []; // simultaneously adding and removing from the same array gets weird, so track removes and trash 'em after
      $fieldName = $node->getType() == 'about' ? 'field_about_resources' : 'field_other_info';
      $aboutResources = $node->get($fieldName)->getValue();
      if(!empty($aboutResources)) {
        foreach($aboutResources as $aboutResource) {
          $aboutResourcesParagraph = Paragraph::load($aboutResource['target_id']);
          if($aboutResourcesParagraph->getType() == 'other_info_card') {
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
              $this->removeOldResources($removes, $aboutResourcesParagraph, 'field_resources');
              $node->save();
            }
          }
        }
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
  public function migrateCampaignResources() {
    $nids = $this->getNodes('campaign');
    foreach($nids as $nid) {
      $node = Node::load($nid);
      $removes = [];
      $fieldContents = $node->get('field_contents')->getValue();
      if(!empty($fieldContents)) {
        foreach($fieldContents as $fieldContent) {
          $campaignResourcesParagraph = Paragraph::load($fieldContent['target_id']);
          if ($campaignResourcesParagraph->getType() == 'campaign_resources') {
            $campaignResourceSections = $campaignResourcesParagraph->get('field_resources')->getValue();
            if(!empty($campaignResourceSections)) {
              foreach($campaignResourceSections as $campaignResourceSection) {
                $campaignResourceSectionParagraph = Paragraph::load($campaignResourceSection['target_id']);
                $resources = $campaignResourceSectionParagraph->get('field_content')->getValue();
                if(!empty($resources)) {
                  foreach($resources as $resource) {
                    $resourceParagraph = Paragraph::load($resource['target_id']);
                    if ($resourceParagraph->getType() == 'resources') {
                      $this->migrateResource($node, $resourceParagraph, $campaignResourceSectionParagraph, 'field_content');
                      $removes[] = $resourceParagraph->id();
                    }
                  }
                  $this->removeOldResources($removes, $campaignResourceSectionParagraph, 'field_content');
                  $node->save();
                }
              }
            }
          }
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
        $this->removeOldResources($removes, $node, 'field_resources');
        $node->save();
      }
    }
  }

  // SG-1644 - resource section subheadings
  public function migrateTopicsAndDepartmentsResourceSubheading() {
    $nids = array_merge($this->getNodes('department'), $this->getNodes('topic'));

    foreach($nids as $nid) {
      // if($nid == 4010) {
        $node = Node::load($nid);
        $removes = [];
        $resources = $node->get('field_resources')->getValue();
        if(!empty($resources)) {
          $resourcesToMove = [];

          // only move non tile section paragraphs
          foreach($resources as $resource) {
            $resourceP = Paragraph::load($resource["target_id"]);
            if($resourceP->getType() != "other_info_card") {
              $resourcesToMove[] = $resource;

              if($resourceP->getType() == 'resource_node') {
                $resourceTitle = Node::load($resourceP->get('field_node')->getValue()[0]['target_id'])->getTitle();
              } else {
                $eckResource = $resourceP->get('field_resource')->getValue();
                $eckId = $eckResource[0]['target_id'];
                $storage = \Drupal::entityTypeManager()->getStorage("resource");
                $eckEntity = $storage->load($eckId);
                $resourceTitle = $eckEntity->get('title')->getValue()[0]['value'];
              }

              $reportResource = [
                'resource_field_link' => $uri,
                'resource_id' => $resourceP->id(),
                'resource_type' => $resourceP->getType(),
                'resource_field_title' => $resourceTitle,
                'node_id' => $node->id(),
                'node_content_type' => $node->getType(),
                'node_title' => $node->getTitle(),
                'node_author' => User::load($node->getOwner()->id())->getDisplayName(),
                'last_updated' => date("m/d/Y", $node->changed->value)
              ];

              $this->nodeReport->addItem($reportResource, $node->id());
              $this->report->addItem($reportResource);
            }
          }
          
          // create new resource section with subheading
          // add existing resources
          $tileSectionParagraph = Paragraph::create([
            "type" => "other_info_card",
            "field_resources" => $resourcesToMove
          ]);
          $tileSectionParagraph->save();
  
          $resources = [
            "target_id" => $tileSectionParagraph->id(),
            "target_revision_id" => $tileSectionParagraph->getRevisionId()
          ];
  
          $node->set('field_resources', $resources);
          $node->save();
        }
      // }
    }
  }

  //
  // resource collections
  // --> field_paragraphs
  // ----> resource_section
  // ------> field_content
  // --------> resource_subsection
  // ----------> field_resources
  public function migrateResourceCollections() {
    $nids = $this->getNodes('resource_collection');
    foreach($nids as $nid) {
      $node = Node::load($nid);
      $removes = [];
      $fieldParagraphs = $node->get('field_paragraphs')->getValue();
      if(!empty($fieldParagraphs)) {
        foreach($fieldParagraphs as $fieldParagraph) {
          $someParagraph = Paragraph::load($fieldParagraph['target_id']);
          if($someParagraph->getType() == 'resource_section') {
            $resourceSubsections = $someParagraph->get('field_content')->getValue();
            foreach($resourceSubsections as $resourceSubsection) {
              $resourceSubsectionParagraph = Paragraph::load($resourceSubsection['target_id']);
              $resources = $resourceSubsectionParagraph->get('field_resources')->getValue();
              foreach($resources as $resource) {
                $resourceParagraph = Paragraph::load($resource['target_id']);
                if ($resourceParagraph->getType() == 'resources') {
                  $this->migrateResource($node, $resourceParagraph, $resourceSubsectionParagraph, 'field_resources');
                  $removes[] = $resourceParagraph->id();
                }
              }
              $this->removeOldResources($removes, $resourceSubsectionParagraph, 'field_resources');
              $node->save();
            }
          }
        }
      }
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
      'last_updated' => date("m/d/Y", $containingNode->changed->value),
      'published_status' => $containingNode->isPublished() ? 'true' : 'false'
    ];
    $this->report->addItem($resource);
    $this->nodeReport->addItem($resource, $containingNode->id());
    if(!empty($uri)) {
      $this->duplicateReport->addItem($resource, $uri);
    }

    if(!$this->dryRun) {
      if(!empty($immediateParentFieldName)) {
        $newResourceParagraph = null;
        $newResource = [];
        if($isEntityRef !== false) {
          $params = Url::fromUri($uri)->getRouteParameters();
          $sfgovLinkParagraph = Paragraph::create([
            "type" => "resource_node"
          ]);
          $sfgovLinkParagraph->field_node->target_id = $params['node'];
          $sfgovLinkParagraph->save();

          $newResourceParagraph = $sfgovLinkParagraph;
          $referencedNode = Node::load($params['node']);
          $newResource = [
            'resource_field_link' => $sfgovLinkParagraph->field_node->target_id,
            'resource_id' => $sfgovLinkParagraph->id(),
            'resource_type' => 'sfgov_link',
            'resource_field_title' => $referencedNode ? $referencedNode->getTitle() : 'empty resource reference',
            'resource_field_description' => $referencedNode->field_description->value,
            'node_id' => $containingNode->id(),
            'node_content_type' => $contentType,
            'node_title' => $containingNode->getTitle(),
            'node_author' => User::load($containingNode->getOwner()->id())->getDisplayName(),
            'last_updated' => date("m/d/Y", $containingNode->changed->value)
          ];
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
          $newResource = [
            'resource_field_link' => $externalLinkEntity->field_url->uri,
            'resource_id' => $externalLinkEntity->id(),
            'resource_type' => 'eck',
            'resource_field_title' => $externalLinkEntity->title->value,
            'resource_field_description' => $externalLinkEntity->field_description->value,
            'node_id' => $containingNode->id(),
            'node_content_type' => $contentType,
            'node_title' => $containingNode->getTitle(),
            'node_author' => User::load($containingNode->getOwner()->id())->getDisplayName(),
            'last_updated' => date("m/d/Y", $containingNode->changed->value)
          ];
        }

        // attach new resource paragraph to immediate parent
        $immediateParent->get($immediateParentFieldName)[] = $newResourceParagraph;
        $immediateParent->save();
        $containingNode->save();

        $this->validationReport->addItem($newResource, $containingNode->id());
      }
    }
  }

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
        "last_updated" => $items[0]['last_updated'],
        "resource_count" => $numItems,
        "resources" => $items,
      ];
    }
    echo json_encode($nodesWithResourceCount, JSON_UNESCAPED_SLASHES);
  }

  public function verifyMigration() {
    $beforeItems = $this->nodeReport->getReport();
    $afterItems = $this->validationReport->getReport();

    $numItemsBefore = count($beforeItems);
    $numItemsAfter = count($beforeItems);
    $valid = false;

    $notOk = [];

    if ($numItemsBefore == $numItemsAfter) { // count is the same, countinue
      $titleKey = 'resource_field_title';
      $descriptionKey = 'resource_field_description';
      $urlKey = 'resource_field_link';
      foreach($beforeItems as $key => $value) {
        // compare before and after
        $beforeResources = $beforeItems[$key];
        $afterResources = $afterItems[$key];

        $beforeResourcesCount = count($beforeResources);
        $afterResourcesCount = count($afterResources);

        if($beforeResourcesCount == $afterResourcesCount) {
          $node = Node::load($key);
          echo "[$key]:" . $node->getTitle() . " (" . $node->getType() . ")\n";
          for($i=0; $i<$beforeResourcesCount; $i++) {
            // verify that title, description, and url are the same
            // verify that resource types are different
            $beforeResource = $beforeResources[$i];
            $afterResource = $afterResources[$i];

            $beforeTitle = $beforeResource[$titleKey];
            $afterTitle = $afterResource[$titleKey];
  
            $beforeDescription = $beforeResource[$descriptionKey];
            $afterDescription = $afterResource[$descriptionKey];
            
            $beforeUrl = $beforeResource[$urlKey];
            $afterUrl = $afterResource[$urlKey];
            
            $sameTitle = $beforeTitle == $afterTitle;
            $sameDescription = ($beforeDescription == $afterDescription) 
              || (empty($beforeDescription) && empty($afterDescription));
            $sameUrl = $afterResource['resource_type'] == 'sfgov_link'
              ? preg_split("/\//", $beforeUrl)[1] == $afterResource[$urlKey] // check the target id
              : $beforeUrl == $afterUrl;

            echo "\t" . $beforeTitle . " (" . $afterResource['resource_type'] . ") ";
            
            if($sameTitle && $sameDescription && $sameUrl) {
              echo ".. [ok]\n";
            } else {
              $diff = [];
              echo ".. [NOT OK] <===========================\n";

              if(!$sameTitle) {
                echo "\t\tbefore title: $beforeTitle\n";
                echo "\t\tafter title: $afterTitle\n";
                $diff[] = 'title';
              }
              if(!$sameDescription) {
                echo "\t\tbefore description: [$beforeDescription]\n";
                echo "\t\tafter description: [$afterDescription]\n";
                $diff[] = 'description';
              }
              if(!$sameUrl) {
                echo "\t\tbefore url: $beforeUrl\n";
                echo "\t\tafter url: $afterUrl\n";
                $diff[] = 'url';
              }

              $notOk[] = [
                'nid' => $key,
                'resource_type' => $afterResource['resource_type'],
                'content_type' => $node->getType(),
                'content_title' => $node->getTitle(),
                'before_title' => "[$beforeTitle]",
                'after_title' => "[$afterTitle]",
                'before_description' => "[$beforeDescription]",
                'after_description' => "[$afterDescription]",
                'before_url' => $beforeUrl,
                'after_url' => $afterUrl,
                'diff' => implode(', ', $diff),
              ];
            }
          }
          echo "\n";
        } else {
          echo "resource count for node id $key is not the same\n";
        }
      }
      if(!empty($notOk)) {
        echo "not ok stuff:\n";
        print_r($notOk);
        echo "\n\n";
        echo json_encode($notOk, JSON_UNESCAPED_SLASHES);
      }
    } else {
      echo "processed node count is not the same\n";
    }
  }
}
