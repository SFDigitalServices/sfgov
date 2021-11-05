<?php

namespace Drupal\media_revisions_ui\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\Routing\Route;
use Drupal\media\Access\MediaRevisionAccessCheck as CoreMediaRevisionAccessCheck;

/**
 * Provides an access checker for media item revisions.
 *
 * Core media access class allows only view operation so it is extended to
 * provide access checks for revert and delete operations as well.
 *
 * @ingroup media_access
 */
class MediaRevisionAccessCheck extends CoreMediaRevisionAccessCheck {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type_manager);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, AccountInterface $account, $media_revision = NULL, MediaInterface $media = NULL) {
    if ($media_revision) {
      $media = $this->mediaStorage->loadRevision($media_revision);
    }

    return parent::access($route, $account, $media_revision, $media)->addCacheTags([
      'media:' . $media->id() . ':revisions_list',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(MediaInterface $media, AccountInterface $account, $op = 'view') {
    $bundle = $media->bundle();
    $operations = [
      'view' => 'view all media revisions',
      'update' => 'revert all media revisions',
      'delete' => 'delete all media revisions',
    ];
    $bundleOperations = [
      'view' => "view {$bundle} media revisions",
      'update' => "revert {$bundle} media revisions",
      'delete' => "delete {$bundle} media revisions",
    ];

    if (!$media || !isset($operations[$op]) || !isset($bundleOperations[$op])) {
      // If there was no media to check against, or the $op was not one of the
      // supported ones, we return access denied.
      return FALSE;
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $langcode = $media->language()->getId();
    $cid = $media->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $op;

    if (!isset($this->access[$cid])) {
      // Perform basic permission checks first.
      if (
        !$account->hasPermission($operations[$op]) &&
        !$account->hasPermission($bundleOperations[$op]) &&
        !$account->hasPermission('administer media')
      ) {
        $this->access[$cid] = FALSE;
        return FALSE;
      }

      $mediaType = $media->getEntityType()->getBundleEntityType();
      /** @var \Drupal\media\MediaTypeInterface $mediaTypeEntity */
      $mediaTypeEntity = $this->entityTypeManager->getStorage($mediaType)->load($media->bundle());
      // If the revisions checkbox is selected for the media type, display the
      // revisions tab.
      if ($mediaTypeEntity->shouldCreateNewRevision() && $op === 'view') {
        $this->access[$cid] = TRUE;
      }
      else {
        // There should be at least two revisions. If the vid of the given media
        // and the vid of the default revision differ, then we already have two
        // different revisions so there is no need for a separate database
        // check. Also, if you try to revert to or delete the default revision,
        // that's not good.
        if ($media->isDefaultRevision() && ($this->countDefaultLanguageRevisions($media) == 1 || $op === 'update' || $op === 'delete')) {
          $this->access[$cid] = FALSE;
        }
        elseif ($account->hasPermission('administer media')) {
          $this->access[$cid] = TRUE;
        }
        else {
          // First check the access to the default revision and finally, if the
          // media passed in is not the default revision then access to that,
          // too.
          $this->access[$cid] = $this->mediaAccess->access($this->mediaStorage->load($media->id()), $op, $account) && ($media->isDefaultRevision() || $this->mediaAccess->access($media, $op, $account));
        }
      }
    }

    return $this->access[$cid];
  }

}
