<?php

namespace Drupal\sfgov_data\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\jsonapi_extras\EntityToJsonApi;

/**
 * JsonApiBuilder service
 */
class JsonApiBuilder {
  /**
   * The EntityToJsonApi service
   * 
   * @var \Drupal\jsonapi_extras\EntityToJsonApi
   */
  protected $entityToJsonApi;

  /**
   * The EntityFieldManager
   * 
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a JsonApiBuilder object
   * 
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entity_to_jsonapi
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(EntityToJsonApi $entity_to_jsonapi, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityToJsonApi = $entity_to_jsonapi;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Method description.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return string
   * @throws \Exception
   */
  public function buildJsonApi(EntityInterface $entity): string {
    $includes = $this->getIncludes($entity);
    return $this->entityToJsonApi->serialize($entity, $includes);
  }

  /**
   * Recursively retrieve an array of field paths that's suitable for the
   * include parameter of a JSON:API request
   * 
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * 
   * @return array
   */
  public function getIncludes(EntityInterface $entity): array {
    // Field types that can reference other entities
    $field_types = [
      'entity_reference_revisions',
      'entity_reference',
      'image',
    ];

    // Referencable entities that we want to include
    $target_types = [
      'paragraph',
      'media',
      'file',
      'node',
    ];

    $includes = [];

    // Iterate the fields of an entity and look for the fields that can reference other entities
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());

    foreach ($field_definitions as $field_definition) {
      if (!$field_definition->getFieldStorageDefinition()->isBaseField()) {
        // only include specific field types
        if (in_array($field_definition->getType(), $field_types)) {
          // only include specific entities
          if (in_array($field_definition->getSetting('target_type'), $target_types)) {
            $includes[] = $field_definition->getName();

            // get the referenced entities and also get their relationships
            $referenced_entities = $entity->get($field_definition->getName())->referencedEntities();
            foreach ($referenced_entities as $referenced_entity) {
              $_includes = $this->getIncludes($referenced_entity);
              foreach ($_includes as $_include) {
                $full_path = $field_definition->getName() . '.' . $_include;
                if (!in_array($full_path, $includes)) {
                  $includes[] = $full_path;
                }
              }
            }
          }
        }
      }
    }

    return $includes;
  }
}
