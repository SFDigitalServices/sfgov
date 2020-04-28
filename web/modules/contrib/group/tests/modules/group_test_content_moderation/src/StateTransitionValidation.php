<?php

namespace Drupal\group_test_content_moderation;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Decorate the state transition validation service to ensure compatibility.
 */
class StateTransitionValidation implements StateTransitionValidationInterface {

  /**
   * The inner service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $inner;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Constructs the service decorator.
   */
  public function __construct(StateTransitionValidationInterface $inner, ModerationInformationInterface $moderation_information) {
    $this->inner = $inner;
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidTransitions(ContentEntityInterface $entity, AccountInterface $user) {
    $transitions = $this->inner->getValidTransitions($entity, $user);

    // Always allow the archive transition for testing purposes.
    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $transitions['archive'] = $workflow->getTypePlugin()->getTransition('archive');

    return $transitions;
  }

  /**
   * {@inheritdoc}
   */
  public function isTransitionValid(WorkflowInterface $workflow, StateInterface $original_state, StateInterface $new_state, AccountInterface $user, ContentEntityInterface $entity = NULL) {
    // We can only make a determination if we have the entity, otherwise we
    // won't be able to reference the participants.
    if ($entity) {
      // As this may be occurring during validation, the moderation state on the
      // entity may be the new state, rather than the current state, so make
      // sure we're working with the current version.
      $original_entity = $entity->isNew() ? $entity : $this->entityTypeManager->getStorage($entity->getEntityTypeId())->loadRevision($entity->getLoadedRevisionId());
      $transition = $workflow->getTypePlugin()->getTransitionFromStateToState($original_state->id(), $new_state->id());
      return in_array($transition->id(), array_keys($this->getValidTransitions($original_entity, $user)));
    }

    return FALSE;
  }

}
