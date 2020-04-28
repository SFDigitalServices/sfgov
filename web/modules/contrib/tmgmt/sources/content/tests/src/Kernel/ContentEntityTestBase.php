<?php

namespace Drupal\Tests\tmgmt_content\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Base class for content entity kernel tests.
 *
 * @group tmgmt
 */
abstract class ContentEntityTestBase extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'tmgmt',
    'tmgmt_content',
    'tmgmt_test',
    'language',
    'content_translation',
    'options',
  ];

  protected $entityTypeId = 'entity_test_mul';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add the languages.
    $this->installConfig(['language']);
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('cs')->save();

    $this->installEntitySchema('tmgmt_job');
    $this->installEntitySchema('tmgmt_job_item');
    $this->installEntitySchema('tmgmt_remote');
    $this->installEntitySchema('tmgmt_message');
    $this->installEntitySchema('entity_test_mul');
    $this->container->get('content_translation.manager')->setEnabled('entity_test_mul', 'entity_test_mul', TRUE);
    $this->installSchema('system', array('router'));

    \Drupal::service('router.builder')->rebuild();

    tmgmt_translator_auto_create(\Drupal::service('plugin.manager.tmgmt.translator')->getDefinition('test_translator'));
  }

}
