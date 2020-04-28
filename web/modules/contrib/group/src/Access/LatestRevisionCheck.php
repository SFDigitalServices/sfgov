<?php

namespace Drupal\group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Check access to the latest revision in group content.
 */
class LatestRevisionCheck implements AccessInterface {

  /**
   * The content moderation latest version access service.
   *
   * @var \Drupal\content_moderation\Access\LatestRevisionCheck
   */
  protected $inner;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the group latest revision check object.
   *
   * @param \Drupal\Core\Routing\Access\AccessInterface $inner
   *   The inner service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(AccessInterface $inner, EntityTypeManagerInterface $entity_type_manager) {
    $this->inner = $inner;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $access = $this->inner->access($route, $route_match, $account);
    if (!$access->isAllowed()) {
      // Check for group-specific access.
      $entity = $this->loadEntity($route, $route_match);
      if ($entity) {
        $group_access = $this->checkGroupAccess($entity, $account);
        $access = $access->orIf($group_access);
      }
    }
    return $access;
  }

  /**
   * Determine group-specific access to the latest revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Returns allowed access if the entity belongs to a group, and the user
   *   has both the 'view latest version' and the
   *   'view unpublished PLUGIN_ID entity' permission in a group it belongs to.
   */
  protected function checkGroupAccess(ContentEntityInterface $entity, AccountInterface $account) {
    $access = AccessResultNeutral::neutral();
    $plugin_id = 'group_' . $entity->getEntityTypeId() . ':' . $entity->bundle();

    // Only act if there are group content types for this entity bundle.
    $group_content_types = $this->entityTypeManager->getStorage('group_content_type')->loadByContentPluginId($plugin_id);
    if (empty($group_content_types)) {
      return $access;
    }

    // Load all the group content for this entity.
    $group_contents = $this->entityTypeManager
      ->getStorage('group_content')
      ->loadByProperties([
        'type' => array_keys($group_content_types),
        'entity_id' => $entity->id(),
      ]);

    // If the entity does not belong to any group, we have nothing to say.
    if (empty($group_contents)) {
      return $access;
    }

    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    foreach ($group_contents as $group_content) {
      $group = $group_content->getGroup();
      $access = $access->orIf(AccessResult::allowedIf(
        $group->hasPermission('view latest version for ' . $plugin_id, $account)
        && $group->hasPermission('view unpublished ' . $plugin_id . ' entity', $account)
      ));
      $access->addCacheableDependency($group_content);
      $access->addCacheableDependency($group);
    }

    return $access;
  }

  /**
   * Copy of content moderation's protected method.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   returns the Entity in question.
   *
   * @throws \Exception
   *   A generic exception is thrown if the entity couldn't be loaded. This
   *   almost always implies a developer error, so it should get turned into
   *   an HTTP 500.
   */
  protected function loadEntity(Route $route, RouteMatchInterface $route_match) {
    $entity_type = $route->getOption('_content_moderation_entity_type');

    if ($entity = $route_match->getParameter($entity_type)) {
      if ($entity instanceof ContentEntityInterface) {
        return $entity;
      }
    }
    throw new \Exception(sprintf('%s is not a valid entity route. The LatestRevisionCheck access checker may only be used with a route that has a single entity parameter.', $route_match->getRouteName()));
  }

}
