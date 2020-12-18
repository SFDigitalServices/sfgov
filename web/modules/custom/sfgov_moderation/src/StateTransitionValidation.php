<?php

namespace Drupal\sfgov_moderation;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidation as CoreStateTransitionValidation;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sfgov_departments\SFgovDepartment;
use Drupal\workflows\StateInterface;
use Drupal\workflows\TransitionInterface;
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
   * Transitions allowed for a reviewer role.
   *
   * @var string[]
   */
  protected static $reviewerAllowedTransitions = [
    'archived_draft', // Restore to Draft.
    'create_new_draft', // Create New Draft.
    'publish', // Publish.
    'submit_for_review', // Submit for review.
  ];

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
    /** @var \Drupal\node\NodeInterface $entity */

    $validTransitions = parent::getValidTransitions($entity, $user);
    $fieldName = SFgovDepartment::getDepartmentFieldName($entity->bundle());

    // For admins, new content or other entity types, inherit behavior.
    if ($entity->getEntityTypeId() != 'node' ||
      $entity->isNew() ||
      $user->hasPermission('administer nodes') ||
      $user->hasPermission('bypass node access') ||
     empty($fieldName) ||
      !$entity->hasField($fieldName) ||
      (!$departments = $entity->{$fieldName}->referencedEntities())
    ) {
      return $validTransitions;
    }

    // Act on moderated content that belongs to a group.

    // Restrict transitions based if $user is the reviewer.
    if (($reviewer = $entity->reviewer->target_id) && $reviewer == $user->id()) {
      return array_filter($this->getAllTransitionsFromState($entity), function (TransitionInterface $transition) {
        return in_array($transition->id(), static::$reviewerAllowedTransitions);
      });
    }

    // Finally, only allow transitions if current user (non-admin) belongs to the department.
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

  /**
   * Get all transitions from a given state.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return \Drupal\workflows\TransitionInterface[]
   *   The transitions.
   */
  protected function getAllTransitionsFromState(ContentEntityInterface $entity) {
    $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
    $current_state = $entity->moderation_state->value ?
      $workflow->getTypePlugin()->getState($entity->moderation_state->value) :
      $workflow->getTypePlugin()->getInitialState($entity);

    return $current_state->getTransitions();
  }

}
