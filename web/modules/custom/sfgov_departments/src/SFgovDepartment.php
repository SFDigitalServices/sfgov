<?php

namespace Drupal\sfgov_departments;

use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;

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

//    $group_storage = \Drupal::entityManager()->getStorage('group');
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

  protected static function addNodeToGroup(NodeInterface $node, GroupInterface $group) {
    $entity_type_manager = \Drupal::entityTypeManager();

    $query = \Drupal::entityQuery('group_content')
      ->condition('gid', $group->id())
      ->condition('entity_id', $node->id())
      ->range(0, 1);
    $ids = $query->execute();

    if (empty($ids)) {
      /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $group_content_storage */
      $group_content_storage = $entity_type_manager->getStorage('group_content');
      $group_content = $group_content_storage->create([
        'type' => 'department-group_node-department',
        'gid' => $group->id(),
        'entity_id' => $node->id(),
        'label' => $node->getTitle(),
      ]);
      $group_content->save();
    }
  }

  public static function updateGroupContent(NodeInterface $node) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $group_content_storage = $entity_type_manager->getStorage('group_content');

    // Get previous groups.
    $previous_group_ids = [];
    $query = \Drupal::entityQuery('group_content')
      ->condition('type', 'department-group_node-' . $node->bundle())
      ->condition('entity_id', $node->id())
      ->range(0, 1);
    $previous_department_ids = $query->execute();
    $content_in_groups = $group_content_storage->loadMultiple($previous_department_ids);
    foreach ($content_in_groups as $group_content) {
      $group = reset($group_content->gid->referencedEntities());
      $previous_group_ids[$group->id()] = $group_content;
    }

    // Add to new departments.
    $departments = $node->get('field_departments')->referencedEntities();
    foreach ($departments as $department) {
      if (in_array($department->id(), $previous_department_ids)) {
        unset($previous_department_ids[$department->id()]);
      }
      else {
        SFgovDepartment::addNodeToGroupByDepartmentNode($node, $department);
      }
    }

    // Delete from previous/removed departments.
    if ($previous_group_ids) {
      $group_content_storage->delete($previous_group_ids);
    }
  }

  public static function addNodeToGroupByDepartmentNode(NodeInterface $node, NodeInterface $department) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $sf_gov_department = new self($department);
    $group = $sf_gov_department->getDepartmentGroup();

    $query = \Drupal::entityQuery('group_content')
      ->condition('gid', $group->id())
      ->condition('entity_id', $node->id())
      ->range(0, 1);
    $ids = $query->execute();

    if (empty($ids)) {
      /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $group_content_storage */
      $group_content_storage = $entity_type_manager->getStorage('group_content');
      $group_content = $group_content_storage->create([
        'type' => 'department-group_node-' . $node->bundle(),
        'gid' => $group->id(),
        'entity_id' => $node->id(),
        'label' => $node->getTitle(),
      ]);
      $group_content->save();
    }
  }

}
