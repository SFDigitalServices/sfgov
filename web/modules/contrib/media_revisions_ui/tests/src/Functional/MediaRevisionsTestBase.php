<?php

namespace Drupal\Tests\media_revisions_ui\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides base class with common methods to use in tests.
 */
abstract class MediaRevisionsTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The media storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $this->mediaStorage = $entityTypeManager->getStorage('media');
  }

  /**
   * Creates a new user and logs in.
   *
   * @param array $permissions
   *   List of permissions to assign to the user.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createUserWithPermissionsAndLogin(array $permissions) {
    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);
  }

  /**
   * Creates a new revision for a given media item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $media
   *   Media to create revision in.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A media object with up to date revision information.
   */
  protected function createMediaRevision(EntityInterface $media) {
    $media->setName($this->randomMachineName());
    $media->setNewRevision();
    $media->save();

    return $media;
  }

  /**
   * Loads oldest revision from media.
   *
   * @param \Drupal\Core\Entity\EntityInterface $media
   *   The media from which to load revision.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The revision entity.
   */
  protected function loadOldestRevisionId(EntityInterface $media) {
    $result = $this->mediaStorage->getQuery()
      ->allRevisions()
      ->condition('mid', $media->id())
      ->sort('vid', 'ASC')
      ->range(NULL, 1)
      ->execute();

    $revisionId = array_keys($result) ? array_keys($result)[0] : NULL;

    return $this->mediaStorage->loadRevision($revisionId);
  }

}
