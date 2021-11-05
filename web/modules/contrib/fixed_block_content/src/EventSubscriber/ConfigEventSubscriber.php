<?php

namespace Drupal\fixed_block_content\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\fixed_block_content\FixedBlockContentInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber that manages the auto-export feature.
 */
class ConfigEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The used lock backend instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * Constructs SetFixedBlockDependency object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend to check if a config import is ongoing.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LockBackendInterface $lock) {
    $this->entityTypeManager = $entity_type_manager;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => 'onConfigSave',
    ];
  }

  /**
   * Do the automatic default content export if default content has changed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The config event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $config_name = $config->getName();

    // Work only with fixed block content configurations.
    if (strpos($config_name, 'fixed_block_content.fixed_block_content.') !== 0) {
      return;
    }

    // Work only if we are involved in a config import process, that is, the
    // config importer lock key is busy. We cannot relay on the
    // ConfigEvents::IMPORT event because we need to compare the updated
    // default_content with the original.
    if ($this->lock->lockMayBeAvailable(ConfigImporter::LOCK_NAME)) {
      // The config importer is not locked, do nothing.
      return;
    }

    // Nothing to do if no auto-export option set.
    if (empty($config->get('auto_export'))) {
      return;
    }

    /** @var \Drupal\fixed_block_content\FixedBlockContentInterface $fixed_block */
    $fixed_block = $this->entityTypeManager
      ->getStorage('fixed_block_content')
      ->load($config->get('id'));
    if (!$fixed_block) {
      return;
    }

    // For any auto-export option, crete new block content if it doesn't exist.
    if (!$fixed_block->getBlockContent(FALSE)) {
      $fixed_block->exportDefaultContent();
    }
    elseif ($fixed_block->getAutoExportState() == FixedBlockContentInterface::AUTO_EXPORT_ALWAYS
      && !empty($config->get('default_content'))
      && $event->isChanged('default_content')) {
      // The unconditional auto-export updates the existing block content.
      $fixed_block->exportDefaultContent(TRUE);
    }
  }

}
