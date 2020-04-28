<?php

namespace Drupal\Tests\tmgmt_content\Kernel;

use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Content entity Source unit tests.
 *
 * @group tmgmt
 */
class ContentEntityMetatagTest extends ContentEntityTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['metatag'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'field_meta_tags',
      'entity_type' => $this->entityTypeId,
      'type' => 'metatag',
      'cardinality' => 1,
      'translatable' => TRUE,
    ));
    $field_storage->save();
    FieldConfig::create(array(
      'entity_type' => $this->entityTypeId,
      'field_storage' => $field_storage,
      'bundle' => $this->entityTypeId,
      'label' => 'Meta tags',
    ))->save();

    $this->installConfig(['metatag']);
  }

  /**
   * Tests the metatag integration.
   */
  public function testMetatagsField() {
    // Create an english test entity.
    $values = [
      'langcode' => 'en',
      'user_id' => 1,
      'name' => 'Test entity',
      'field_meta_tags' => [
        'value' => serialize([
          'description' => 'The description',
          'robots' => 'noindex,nofollow',
          'referer' => 'origin',
          'news_keywords' => 'Sport',
        ])
      ]
    ];
    $entity_test = EntityTestMul::create($values);
    $entity_test->save();

    $job = tmgmt_job_create('en', 'de');
    $job->translator = 'test_translator';
    $job->save();
    $job_item = tmgmt_job_item_create('content', $this->entityTypeId, $entity_test->id(), array('tjid' => $job->id()));
    $job_item->save();

    $source_plugin = $this->container->get('plugin.manager.tmgmt.source')->createInstance('content');
    $data = $source_plugin->getData($job_item);

    // Test the expected structure of the metatags field.
    $expected_field_data = [
      'basic' => [
        '#label' => 'Basic tags',
        'description' => [
          '#translate' => TRUE,
          '#text' => 'The description',
          '#label' => 'Description',
        ],
      ],
      'advanced' => [
        '#label' => 'Advanced',
        'robots' => [
          '#translate' => FALSE,
          '#text' => 'noindex,nofollow',
          '#label' => 'Robots',
        ],
        'news_keywords' => [
          '#translate' => TRUE,
          '#text' => 'Sport',
          '#label' => 'News Keywords',
        ],
      ],
      '#label' => 'Meta tags',
    ];
    $this->assertEquals($expected_field_data, $data['field_meta_tags']);

    // Now request a translation and save it back.
    $job->requestTranslation();
    $items = $job->getItems();
    $item = reset($items);
    $item->acceptTranslation();
    $data = $item->getData();

    // Check that the translations were saved correctly.
    $entity_test = EntityTestMul::load($entity_test->id());
    $translation = $entity_test->getTranslation('de');
    $this->assertEquals($translation->name->value, $data['name'][0]['value']['#translation']['#text']);

    $translated_meta_tags = unserialize($translation->get('field_meta_tags')->value);
    $expected_meta_tags = [
      'description' => 'de(de-ch): The description',
      'robots' => 'noindex,nofollow',
      'news_keywords' => 'de(de-ch): Sport',
    ];
    $this->assertEquals($expected_meta_tags, $translated_meta_tags);

  }

}
