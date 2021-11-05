<?php

namespace Drupal\Tests\fixed_block_content\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Lock\PersistentDatabaseLockBackend;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Basic setup for fixed block content kernel tests.
 */
abstract class FixedBlockContentKernelTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'block',
    'block_content',
    'hal',
    'serialization',
    'fixed_block_content',
    'fixed_block_content_test',
    'text',
    'filter',
    'user',
    'system',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The fixed block to work with.
   *
   * @var \Drupal\fixed_block_content\FixedBlockContentInterface
   */
  protected $fixedBlock;

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    // Set a real lock service.
    $container->setDefinition('lock', new Definition(PersistentDatabaseLockBackend::class, [$container->get('database')]));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
    $this->installSchema('fixed_block_content', ['fixed_block_content']);
    $this->installConfig(['system', 'block_content', 'filter']);

    $this->createBasicBlockType();
    $this->createFixedBlock();
    $this->installConfig(['fixed_block_content_test']);
  }

  /**
   * Creates the basic custom block type.
   */
  protected function createBasicBlockType() {
    // Create a content block type.
    $type = $this->entityTypeManager->getStorage('block_content_type')
      ->create([
        'id' => 'basic',
        'label' => 'Basic',
        'revision' => FALSE,
      ]);
    $type->save();

    // Adds the body field.
    block_content_add_body_field($type->id());
  }

  /**
   * Creates the basic custom block type.
   */
  protected function createFixedBlock() {
    // Create a fixed content block.
    $this->fixedBlock = $this->entityTypeManager
      ->getStorage('fixed_block_content')
      ->create([
        'id' => 'basic_fixed',
        'title' => 'Basic fixed',
        'block_content_bundle' => 'basic',
      ]);

    $this->fixedBlock->save();
  }

}
