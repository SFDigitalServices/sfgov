<?php

namespace Drupal\fixed_block_content\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieves block plugin definitions for all custom config blocks.
 */
class FixedBlockContent extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The custom config block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockConfigStorage;

  /**
   * Constructs a BlockContent object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_storage
   *   The custom block storage.
   */
  public function __construct(EntityStorageInterface $block_content_storage) {
    $this->blockConfigStorage = $block_content_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('fixed_block_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    /** @var \Drupal\fixed_block_content\FixedBlockContentInterface $fixed_block */
    foreach ($this->blockConfigStorage->loadMultiple() as $fixed_block) {
      $this->derivatives[$fixed_block->id()] = $base_plugin_definition;
      $this->derivatives[$fixed_block->id()]['admin_label'] = $fixed_block->label();
      $this->derivatives[$fixed_block->id()]['config_dependencies'][$fixed_block->getConfigDependencyKey()] = [
        $fixed_block->getConfigDependencyName(),
      ];
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
