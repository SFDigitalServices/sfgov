<?php

namespace Drupal\sfgov_moderation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * Moderation Service.
 */
class ModerationUtilService implements ModerationUtilServiceInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ModerationUtilService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $currentUser) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidReviewers(): array {

    $query = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->condition('uid', 0, '>')
      ->condition('status', 1)
      ->sort('name', 'DESC');

    $ids = $query->execute();

    // Remove current user from the reviewer options list.
    $current_user_id = $this->currentUser->id();
    unset($ids[$current_user_id]);

    return $ids ? array_values($ids) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function canPublishFromDraftWithoutReviewer(AccountInterface $account): bool {
    if (!in_array(static::PUBLISHER_ROLE, $account->getRoles())) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getModerationFields(Node $node): array {

    /** @var \Drupal\node\Entity\Node $revision */
    $revision = $this->getLatestRevision($node);

    // Get State.
    $state = $revision->moderation_state->getValue();

    // Get Reviewer.
    $reviewer = $revision->reviewer->getValue();
    if (isset($reviewer[0]['target_id'])) {

      /** @var \Drupal\user\Entity\User $accountEntity */
      $accountEntity = $this->entityTypeManager->getStorage('user')->load($reviewer[0]['target_id']);

      $username = $accountEntity->getDisplayName();
    }

    return [
      'state' => $state[0]['value'] ?? '',
      'username' => $username ?? NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevision(Node $node): Node {

    $nid = $node->id();

    $vid = $this->entityTypeManager
      ->getStorage('node')
      ->getLatestRevisionId($nid);

    /** @var \Drupal\node\Entity\Node $revision */
    $revision = $this->entityTypeManager
      ->getStorage('node')
      ->loadRevision($vid);

    return $revision;
  }

}
