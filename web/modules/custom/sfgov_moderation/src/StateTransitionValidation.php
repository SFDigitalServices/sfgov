<?php

namespace Drupal\sfgov_moderation;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidation as CoreStateTransitionValidation;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
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
  protected const REVIEWER_ALLOWED_TRANSITIONS = [
    'archived_draft', // Restore to Draft.
    'create_new_draft', // Create New Draft.
    'publish', // Publish.
    'submit_for_review', // Submit for review.
  ];

  /**
   * Transitions allowed for an author.
   *
   * @var string[]
   */
  protected const AUTHOR_ALLOWED_TRANSITIONS = [
    'create_new_draft', // Create New Draft.
    'submit_for_review', // Submit for review.
  ];

  /**
   * State flows allowed for a reviewer role.
   *
   * Reviewer allowed transitions. Array is keyed by the IDs of the
   * original states, and each value is an array of valid new states.
   *
   * @var array
   */
  protected const REVIEWER_ALLOWED_STATE_FLOWS = [
    'draft' => [],
    'ready_for_review' => [
      'ready_for_review',
      'published',
    ],
    'publish' => [
      'draft',
      'published',
    ],
    'archived' => [
      'draft',
    ],
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
    $fieldName = $this->moderationUtil->getDepartmentFieldName($entity->bundle());

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

    // Act on moderated content that belongs to a department.

    // Restrict transitions based if $user is the reviewer.
    if ($this->userIsReviewer($entity, $user)) {
      return array_filter($this->getAllTransitionsFromState($entity), function (TransitionInterface $transition) {
        return in_array($transition->id(), static::REVIEWER_ALLOWED_TRANSITIONS);
      });
    }

    // Restrict transitions based if $user is the author.
    if ($this->userIsAuthor($entity, $user)) {
      return array_filter($this->getAllTransitionsFromState($entity), function (TransitionInterface $transition) {
        return in_array($transition->id(), static::AUTHOR_ALLOWED_TRANSITIONS);
      });
    }

    // If node has no departments, allow access.
    if (empty($departments)) {
      return $validTransitions;
    }

    // Finally, if it does have departments, only allow transitions if current user (non-admin) belongs to the department.
    foreach ($departments as $department) {
      if ($accountBelongsToDepartment = $this->moderationUtil->accountBelongsToDepartment($user->getAccount(), $department->id())) {
        break;
      }
    }

    return $accountBelongsToDepartment ? $validTransitions : [];
  }

  /**
   * {@inheritdoc}
   */
  public function isTransitionValid(WorkflowInterface $workflow, StateInterface $original_state, StateInterface $new_state, AccountInterface $user, ContentEntityInterface $entity = NULL) {
    if ($entity === NULL) {
      @trigger_error(sprintf('Omitting the $entity parameter from %s is deprecated and will be required in Drupal 9.0.0.', __METHOD__), E_USER_DEPRECATED);
    }
    $transition = $workflow->getTypePlugin()->getTransitionFromStateToState($original_state->id(), $new_state->id());

    // Allow if user has transition permission granted by role.
    if ($user->hasPermission('use ' . $workflow->id() . ' transition ' . $transition->id())) {
      return TRUE;
    }

    // Allow if user is the author and state flow is allowed.
    if ($this->userIsAuthor($entity, $user)
      && isset(static::REVIEWER_ALLOWED_STATE_FLOWS[$original_state->id()])
      && in_array($new_state->id(), static::REVIEWER_ALLOWED_STATE_FLOWS[$original_state->id()])
    ) {
      return TRUE;
    }

    // Allow if user is the reviewer and state flow is allowed.
    if ($this->userIsReviewer($entity, $user)
      && isset(static::REVIEWER_ALLOWED_STATE_FLOWS[$original_state->id()])
      && in_array($new_state->id(), static::REVIEWER_ALLOWED_STATE_FLOWS[$original_state->id()])
    ) {
      return TRUE;
    }

    // Invalid in all other cases.
    return FALSE;
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

  /**
   * Determine if the given user is a "reviewer" of the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user.
   *
   * @return bool
   *   Boolean value.
   */
  protected function userIsReviewer(ContentEntityInterface $entity, AccountInterface $user): bool {
    if (!$entity->hasField('reviewer')) {
      return FALSE;
    }

    $reviewerId = $entity->reviewer->target_id;
    return (!empty($reviewerId) && $reviewerId == $user->id());
  }

  /**
   * Determine if the given user is an "author" of the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user.
   *
   * @return bool
   *   Boolean value.
   */
  protected function userIsAuthor(ContentEntityInterface $entity, AccountInterface $user): bool {
    return $entity->getOwnerId() == $user->id();
  }

}
