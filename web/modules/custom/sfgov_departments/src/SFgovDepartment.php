<?php

namespace Drupal\sfgov_departments;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupType;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;

/**
 * Class SFgovDepartment
 *
 * Handles automatic creation/sync of department nodes and department groups,
 * as well as helper methods to add content to the department group.
 */
class SFgovDepartment {

  /**
   * @var \Drupal\node\NodeInterface Department node reference.
   */
  protected $department_node;

  /**
   * @var \Drupal\group\Entity\GroupInterface Department group reference.
   */
  protected $department_group;

  public function __construct(NodeInterface $department_node) {
    $this->department_node = $department_node;
  }

  /**
   * Get the department group of this object.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   */
  public function getDepartmentGroup() {
    if (empty($this->department_group)) {
      $entity_type_manager = \Drupal::entityTypeManager();
      $query = \Drupal::entityQuery('group')
        ->condition('field_department', $this->department_node->id())
        ->range(0, 1);
      $ids = $query->execute();

      $this->department_group = empty($ids) ?
        NULL :
        $entity_type_manager->getStorage('group')->load(reset($ids));
    }

    return $this->department_group;
  }

  /**
   * Get the department node given a department group.
   *
   * @return \Drupal\node\NodeInterface|null
   */
  public static function getDepartmentNode(\Drupal\group\Entity\GroupInterface $group) {
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin('group_node:department');

    $entity_type_manager = \Drupal::entityTypeManager();
    $query = \Drupal::entityQuery('group_content')
      ->condition('type', $plugin->getContentTypeConfigId())
      ->condition('gid', $group->id())
      ->range(0, 1);
    $ids = $query->execute();

    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    $group_content = $entity_type_manager->getStorage('group_content')->load(reset($ids));
    return $group_content ? $group_content->getEntity() : NULL;
  }

  /**
   * Create/update the related department group entity when the department node
   * is created or updated.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function createOrUpdate(NodeInterface $node) {
    $entity_type_manager = \Drupal::entityTypeManager();

    // Make sure group entities are always referencing the english translation.
    $department_node = $node;
    if ($node->language()->getId() != 'en' && $node->hasTranslation('en')) {
      $department_node = $node->getTranslation('en');
    }

    // Search for group of this department node.
    $query = \Drupal::entityQuery('group')
      ->condition('field_department', $department_node->id())
      ->range(0, 1);
    $ids = $query->execute();

    /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $group_storage */
    $group_storage = $entity_type_manager->getStorage('group');
    if (empty($ids)) {
      $group = $group_storage->create([
        'type' => 'department',
        'field_department' => $department_node->id(),
      ]);
    }
    else {
      $group = $group_storage->load(reset($ids));
    }

    // Update group values.
    $group->label->value = $department_node->getTitle();
    $group->save();

    // Ensure department node is added to group.
    self::addNodeToGroup($department_node, $group);
  }

  /**
   * Delete the related department group entity when the department node is
   * deleted.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function delete(NodeInterface $node) {
    $query = \Drupal::entityQuery('group')
      ->condition('field_department', $node->id());
    $ids = $query->execute();

    if (!empty($ids)) {
      $group_storage = \Drupal::entityManager()->getStorage('group');
      $groups = $group_storage->loadMultiple($ids);
      $group_storage->delete($groups);
    }
  }

  /**
   * Add a node to a department group.
   *
   * @param \Drupal\node\NodeInterface          $node Node to add to the group.
   * @param \Drupal\group\Entity\GroupInterface $group Group to be added to.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected static function addNodeToGroup(NodeInterface $node, GroupInterface $group) {
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin('group_node:department');
    $entity_type_manager = \Drupal::entityTypeManager();

    $query = \Drupal::entityQuery('group_content')
      ->condition('type', $plugin->getContentTypeConfigId())
      ->condition('gid', $group->id())
      ->condition('entity_id', $node->id())
      ->range(0, 1);
    $ids = $query->execute();

    if (empty($ids)) {
      /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $group_content_storage */
      $group_content_storage = $entity_type_manager->getStorage('group_content');

      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $plugin = $group->getGroupType()->getContentPlugin('group_node:department');

      $group_content = $group_content_storage->create([
        'type' => $plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
        'entity_id' => $node->id(),
        'label' => $node->getTitle(),
      ]);
      $group_content->save();
    }
  }

  /**
   * Updates the groups a node belongs to.
   *
   * @param \Drupal\node\NodeInterface $node Node that will get its groups updated.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function updateGroupContent(NodeInterface $node) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $group_content_storage = $entity_type_manager->getStorage('group_content');

    $plugin_types = [];
    $group_type = GroupType::load('department');
    foreach ($group_type->getInstalledContentPlugins() as $plugin) {
      if ($plugin->getPluginDefinition()['entity_type_id'] == 'node') {
        $plugin_types[] = $plugin->getContentTypeConfigId();
      }
    }

    // Get previous groups.
    $previous_departments_ids = [];
    $query = \Drupal::entityQuery('group_content')
      ->condition('type', $plugin_types, 'IN')
      ->condition('entity_id', $node->id())
      ->range(0, 1);
    $content_in_groups = $group_content_storage->loadMultiple($query->execute());
    foreach ($content_in_groups as $group_content) {
      $referenced = $group_content->gid->referencedEntities();
      $group = reset($referenced);
      $department_node = self::getDepartmentNode($group);
      $previous_departments_ids[$department_node->id()] = $group_content;
    }

    // Add to new departments.
    $departments = $node->get('field_departments')->referencedEntities();
    foreach ($departments as $department_node) {
      if (in_array($department_node->id(), array_keys($previous_departments_ids))) {
        unset($previous_departments_ids[$department_node->id()]);
      }
      else {
        SFgovDepartment::addNodeToGroupByDepartmentNode($node, $department_node);
      }
    }

    // Delete from previous/removed departments.
    if ($previous_departments_ids) {
      $group_content_storage->delete($previous_departments_ids);
    }
  }

  /**
   * Updates the groups an entity belongs to.
   *
   * @param \Drupal\media\MediaInterface $entity Entity that will get its groups updated.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function updateGroupMediaContent(MediaInterface $entity) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $group_content_storage = $entity_type_manager->getStorage('group_content');

    $plugin_types = [];
    $group_type = GroupType::load('department');
    foreach ($group_type->getInstalledContentPlugins() as $plugin) {
      if ($plugin->getPluginDefinition()['entity_type_id'] == 'media') {
        $plugin_types[] = $plugin->getContentTypeConfigId();
      }
    }

    // Get previous groups.
    $previous_departments_ids = [];
    $query = \Drupal::entityQuery('group_content')
      ->condition('type', $plugin_types, 'IN')
      ->condition('entity_id', $entity->id())
      ->range(0, 1);
    $content_in_groups = $group_content_storage->loadMultiple($query->execute());
    foreach ($content_in_groups as $group_content) {
      $referenced = $group_content->gid->referencedEntities();
      $group = reset($referenced);
      if ($department_node = self::getDepartmentNode($group)) {
        $previous_departments_ids[$department_node->id()] = $group_content;
      }
    }

    // Add to new departments.
    $departments = $entity->get('field_department')->referencedEntities();
    foreach ($departments as $department_node) {
      if (in_array($department_node->id(), array_keys($previous_departments_ids))) {
        unset($previous_departments_ids[$department_node->id()]);
      }
      else {
        SFgovDepartment::addMediaToGroupByDepartmentNode($entity, $department_node);
      }
    }

    // Delete from previous/removed departments.
    if ($previous_departments_ids) {
      $group_content_storage->delete($previous_departments_ids);
    }
  }

  /**
   * Add a node to a group given the department node.
   *
   * @param \Drupal\node\NodeInterface $node Node to add to the group.
   * @param \Drupal\node\NodeInterface $department Deparment node.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function addNodeToGroupByDepartmentNode(NodeInterface $node, NodeInterface $department) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $sf_gov_department = new self($department);
    $group = $sf_gov_department->getDepartmentGroup();

    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin('group_node:' . $node->bundle());

    $query = \Drupal::entityQuery('group_content')
      ->condition('type', $plugin->getContentTypeConfigId())
      ->condition('gid', $group->id())
      ->condition('entity_id', $node->id())
      ->range(0, 1);
    $ids = $query->execute();

    if (empty($ids)) {

      /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $group_content_storage */
      $group_content_storage = $entity_type_manager->getStorage('group_content');

      $group_content = $group_content_storage->create([
        'type' => $plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
        'entity_id' => $node->id(),
        'label' => $node->getTitle(),
      ]);
      $group_content->save();
    }
  }

  /**
   * Add media to a group given the department node.
   *
   * @param \Drupal\media\MediaInterface $entity Entity to add to the group.
   * @param \Drupal\node\NodeInterface $department Deparment node.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function addMediaToGroupByDepartmentNode(MediaInterface $entity, NodeInterface $department) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $sf_gov_department = new self($department);
    $group = $sf_gov_department->getDepartmentGroup();
    $entity_type = $entity->getEntityType()->id();

    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin('group_' . $entity_type . ':' . $entity->bundle());

    $query = \Drupal::entityQuery('group_content')
      ->condition('type', $plugin->getContentTypeConfigId())
      ->condition('gid', $group->id())
      ->condition('entity_id', $entity->id())
      ->range(0, 1);
    $ids = $query->execute();

    if (empty($ids)) {

      /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $group_content_storage */
      $group_content_storage = $entity_type_manager->getStorage('group_content');

      $group_content = $group_content_storage->create([
        'type' => $plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
        'entity_id' => $entity->id(),
        'label' => $entity->label(),
      ]);
      $group_content->save();
    }
  }

}
