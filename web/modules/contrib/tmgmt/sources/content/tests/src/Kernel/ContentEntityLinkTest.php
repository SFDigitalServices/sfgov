<?php

namespace Drupal\Tests\tmgmt_content\Kernel;

use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests for the link integration.
 *
 * @group tmgmt
 */
class ContentEntityLinkTest extends ContentEntityTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['link'];

  /**
   * The entity type used for the tests.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_test_mul';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'link',
      'entity_type' => $this->entityTypeId,
      'type' => 'link',
      'cardinality' => 1,
      'translatable' => TRUE,
    ]);
    $field_storage->save();
    FieldConfig::create([
      'entity_type' => $this->entityTypeId,
      'field_storage' => $field_storage,
      'bundle' => $this->entityTypeId,
      'label' => 'Link',
    ])->save();
    $this->container->get('content_translation.manager')->setEnabled($this->entityTypeId, $this->entityTypeId, TRUE);
  }

  /**
   * Tests the link field integration.
   */
  public function testLinkField() {
    // Create an entity with a link.
    $values = [
      'langcode' => 'en',
      'uid' => 1,
      'name' => 'Llama',
      'link' => [
        'title' => 'Llama title',
        'uri' => 'https://www.google.com'
      ],
    ];
    $entity = EntityTestMul::create($values);
    $entity->save();

    $job = tmgmt_job_create('en', 'de');
    $job->translator = 'test_translator';
    $job->save();
    $job_item = tmgmt_job_item_create('content', $this->entityTypeId, $entity->id(), ['tjid' => $job->id()]);
    $job_item->save();

    $source_plugin = $this->container->get('plugin.manager.tmgmt.source')->createInstance('content');
    $data = $source_plugin->getData($job_item);

    // Test the expected structure of the link field.
    $expected_field_data = [
      '#label' => 'Link',
      0 => [
        'uri' => [
          '#label' => 'URI',
          '#text' => 'https://www.google.com',
          '#translate' => FALSE,
        ],
        'title' => [
          '#label' => 'Link text',
          '#text' => 'Llama title',
          '#translate' => TRUE,
        ],
      ],
    ];
    $this->assertEquals($expected_field_data, $data['link']);

    // Now request a translation and save it back.
    $job->requestTranslation();
    $items = $job->getItems();
    $item = reset($items);
    $item->acceptTranslation();

    // Check that the translations were saved correctly.
    $entity = EntityTestMul::load($entity->id());
    $translation = $entity->getTranslation('de');
    $this->assertEquals('https://www.google.com', $translation->get('link')->uri);
    $this->assertEquals('de(de-ch): Llama title', $translation->get('link')->title);

    // Update the URI of the translation.
    $entity = EntityTestMul::load($entity->id());
    $translation = $entity->getTranslation('de');
    $translation->get('link')->uri = 'https://www.google.de';
    // Set a new title to the link in the English translation.
    $entity->get('link')->title = 'Super llama title';
    $entity->save();

    // Create a new job to refresh the German translation.
    $job = tmgmt_job_create('en', 'de');
    $job->translator = 'test_translator';
    $job->save();
    $job_item = tmgmt_job_item_create('content', $this->entityTypeId, $entity->id(), ['tjid' => $job->id()]);
    $job_item->save();

    // Now request a translation and save it back.
    $job->requestTranslation();
    $items = $job->getItems();
    $item = reset($items);
    $item->acceptTranslation();

    // Check that the translations were saved correctly and the customized
    // link URI was not overwritten.
    $entity = EntityTestMul::load($entity->id());
    $translation = $entity->getTranslation('de');
    $this->assertEquals('https://www.google.de', $translation->get('link')->uri);
    $this->assertEquals('de(de-ch): Super llama title', $translation->get('link')->title);
  }

}
