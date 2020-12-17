<?php

namespace Drupal\sfgov_moderation;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidation as CoreStateTransitionValidation;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Validates whether a certain state transition is allowed.
 */
class StateTransitionValidation extends CoreStateTransitionValidation {

  /**
   * Original service instance.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $innerService;

  /**
   * The sfgov_moderation.util service.
   *
   * @var \Drupal\sfgov_moderation\ModerationUtilServiceInterface
   */
  protected $moderationUtil;

  /**
   * Constructs a new StateTransitionValidation.
   *
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $inner_service
   *   Original service instance.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   * @param \Drupal\sfgov_moderation\ModerationUtilServiceInterface $moderation_util
   *   The sfgov_moderation.util service.
   */
  public function __construct(StateTransitionValidationInterface $inner_service, ModerationInformationInterface $moderation_info, ModerationUtilServiceInterface $moderation_util) {
    $this->innerService = $inner_service;
    $this->moderationUtil = $moderation_util;
    parent::__construct($moderation_info);
  }

  /**
   * {@inheritdoc}
   */
  public function getValidTransitions(ContentEntityInterface $entity, AccountInterface $user) {
    $validTransitions = parent::getValidTransitions($entity, $user);

    // For admins, new content or other entity types, inherit behavior.
    if ($entity->getEntityTypeId() != 'node' ||
      $entity->isNew() ||
      $user->hasPermission('administer nodes') ||
      $user->hasPermission('bypass node access') ||
      $entity->hasField(!'field_departments') ||
      (!$departments = $entity->field_departments->referencedEntities())
    ) {
      return $validTransitions;
    }

    /**
     * For moderated content that belongs to a group:
     * Only allow transitions if current user (non-admin) belongs to the department.
     */

    foreach ($departments as $department) {
      if ($accountBelongsToDepartment = $this->moderationUtil->accountBelongsToDepartment($user->getAccount(), $department)) {
        break;
      }
    }

    return $accountBelongsToDepartment ? $validTransitions : [];
  }

  /**
   * {@inheritdoc}
   */
  public function isTransitionValid(WorkflowInterface $workflow, StateInterface $original_state, StateInterface $new_state, AccountInterface $user, ContentEntityInterface $entity = NULL) {
    $transition = $workflow->getTypePlugin()->getTransitionFromStateToState($original_state->id(), $new_state->id());
    return $user->hasPermission('use ' . $workflow->id() . ' transition ' . $transition->id());
  }

}
