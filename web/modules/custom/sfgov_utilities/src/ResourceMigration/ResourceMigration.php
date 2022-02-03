<?php

namespace Drupal\sfgov_utilities\ResourceMigration;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\eck\Entity\EckEntity;
use Drupal\user\Entity\User;

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

  // about
  // --> field_about_resources
  // ----> paragraph: tile_section (other_info_card)
  // ------> field_resources
  // 
  // campaign:
  // --> Additional content (field_contents)
  // ----> Campaign resources (paragraph: campaign_resources)
  // ------> Resources (field_resources)
  // --------> Campaign resource section (paragraph: campaign_resource_section)
  // ----------> Resources (field_content)
  //
  // departments
  // --> field_resources
  //
  // topics
  // --> field_resources
  //
  // resource collections
  // --> field_content_bottom
  // ----> paragraph: Section
  // ------> section content (field_content)
  // --------> resource_section
  // ----------> resource_subsection
  // ------------> field_resources
  public function migrateResources(bool $reportOnly = false) {
    echo "migrateResources\n";
    $pids = \Drupal::entityQuery('paragraph')
    ->condition('type', 'resources')
    ->execute();
    print_r($pids);

    foreach ($pids as $pid) {
      $resourceParagraph = Paragraph::load($pid);
      $parentEntity = $resourceParagraph->getParentEntity();
      $immediateParent = $parentEntity;

      // find the containing node parent
      while($parentEntity->type->entity->bundle() != 'node_type') {
        $parentEntity = $parentEntity->getParentEntity();
      }

      $containingNode = Node::load($parentEntity->id());
      if($containingNode->isPublished()) {
        $contentType = $containingNode->getType();
        $title = $resourceParagraph->field_title->value;
        $description = $resourceParagraph->field_description->value;
        $uri = $resourceParagraph->field_link->uri;
        $isEntityRef = strpos($uri, 'entity:');

        if($isEntityRef !== false) {
          echo "create sf.gov link paragraph\n";
        } else {
          echo "create resource entity\n";
          echo "immediate parent: " . $immediateParent->type->entity->bundle() . "\n";
          echo "immediate parent id: " . $immediateParent->id() . "\n";
          echo "containing content type: " . $contentType . " (" . $containingNode->type->entity->bundle() . ")\n\n";

          $immediateParentFieldName = '';
          switch($contentType) {
            case 'about':
            case 'department':
            case 'resource_collection':
            case 'topic':
              $immediateParentFieldName = 'field_resources';
              break;
            case 'campaign':
              $immediateParentFieldName = 'field_content';
              break;
            default:
              $immediateParentFieldName = '';
          }
          if(!$reportOnly) {
            if(!empty($immediateParentFieldName)) {
              // create eck entity external link, or get an existing one
              $externalLinkEntity = $this->createResourceEntity($title, $description, $uri);
  
              // create paragraph type resource_entity
              $externalLinkParagraph = Paragraph::create([
                "type" => "resource_entity"
              ]);
  
              // attach eck entity to paragraph resource_entity
              $externalLinkParagraph->field_resource = $externalLinkEntity;
  
              // attach paragraph resource_entity to campaign resource section
              $immediateParent->get($immediateParentFieldName)[] = $externalLinkParagraph;
            }
          }
        }

        // $resourceParagraph->field_title->value = $title . " (delete)";
        // $resourceParagraph->save();

        $immediateParent->save();
        $containingNode->save();

        // for reporting
        $resource = [
          'resource_field_link' => $uri,
          'resource_id' => $pid,
          'resource_type' => $resourceParagraph->getType(),
          'resource_field_title' => $title,
          'resource_field_description' => $description,
          'node_id' => $containingNode->id(),
          'node_content_type' => $contentType,
          'node_title' => $containingNode->getTitle(),
          'node_author' => User::load($containingNode->getOwner()->id())->getDisplayName()
        ];
        $this->report->addItem($resource);
        $this->duplicateReport->addItem($resource, $uri);
      }
    }
  }

  public function getReport($json = FALSE) {
    return $this->report->getReport($json);
  }

  public function getDuplicateReport($json = FALSE) {
    return $this->duplicateReport->getReport();
  }
}