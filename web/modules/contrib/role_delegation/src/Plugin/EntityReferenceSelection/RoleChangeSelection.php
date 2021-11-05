<?php

namespace Drupal\role_delegation\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\role_delegation\DelegatableRoles;

/**
 * Class RoleChangeSelection.
 *
 * @EntityReferenceSelection(
 *   id = "role_change:user_role",
 *   label = @Translation("Role change"),
 *   entity_types = {"role"},
 *   group = "role_change",
 *   weight = 0,
 * )
 */
class RoleChangeSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $result = parent::validateReferenceableEntities($ids);

    if ($ids) {
      $result = array_merge($result, DelegatableRoles::$emptyFieldValue);
    }

    return $result;
  }

}
