<?php

namespace Drupal\media_revisions_ui;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for each media revision type.
 */
class MediaRevisionPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MediaPermissions constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of media revision type permissions.
   *
   * @return array
   *   The media type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function mediaRevisionTypePermissions() {
    $perms = [];
    // Generate media revision permissions for all media types.
    $media_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();
    foreach ($media_types as $type) {
      $perms += $this->buildPermissions($type);
    }
    return $perms;
  }

  /**
   * Returns a list of media revision permissions for a given media type.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   The media type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(MediaTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "view {$type_id} media revisions" => [
        'title' => $this->t('%type_name: View media revisions', $type_params),
        'description' => $this->t('To view a revision, you also need permission to view the media item.'),
      ],
      "revert {$type_id} media revisions" => [
        'title' => $this->t('%type_name: Revert media revisions', $type_params),
        'description' => $this->t('To revert a revision, you also need permission to update the media item.'),
      ],
      "delete {$type_id} media revisions" => [
        'title' => $this->t('%type_name: Delete media revisions', $type_params),
        'description' => $this->t('To delete a revision, you also need permission to delete the media item.'),
      ],
    ];
  }

}
