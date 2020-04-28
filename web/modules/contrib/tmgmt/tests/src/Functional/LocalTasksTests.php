<?php

namespace Drupal\Tests\tmgmt\Functional;

/**
 * Verifies basic functionality of the local tasks.
 *
 * @group tmgmt
 */
class LocalTasksTests extends TMGMTTestBase {

  public static $modules = array(
    'dblog',
    'node',
    'views',
    'tmgmt_content',
    'tmgmt_file',
    'tmgmt_config',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Login as administrator to view Cart,Jobs and Sources.
    $this->loginAsAdmin(array('access administration pages'));
  }

  /**
   * Tests UI for translator local tasks.
   */
  public function testTranslatorLocalTasks() {

    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article',
    ));
    $content_translation_manager = \Drupal::service('content_translation.manager');
    // Add a node type and enable translation for nodes and users.
    $content_translation_manager->setEnabled('node', 'article', TRUE);
    $content_translation_manager->setEnabled('user', 'user', TRUE);

    drupal_static_reset();
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('entity.definition_update_manager')->applyUpdates();

    // Check the translator menu link.
    $this->drupalGet('admin');
    $this->clickLink(t('Translation'));

    // Make sure the Cart,Jobs and Sources pages are available.
    $this->clickLink(t('Cart'));
    $this->clickLink(t('Jobs'));
    $this->clickLink(t('Providers'));
    $this->clickLink(t('Settings'));
    $this->clickLink(t('Sources'));

    // Assert the availability of the enabled content.
    $this->assertOptionSelected('edit-source', 'content:node');
    $this->assertOption('edit-source', 'content:node');
    $this->assertOption('edit-source', 'content:user');
    $this->assertOption('edit-source', 'config:block');
    $this->assertOption('edit-source', 'config:node_type');
    $this->assertOption('edit-source', 'config:tmgmt_translator');
    $this->assertNoOption('edit-source', 'config:base_field_override');
  }

  /**
   * Tests UI for translator local tasks without sources.
   */
  public function testTranslatorLocalTasksNoSource() {
    // Login as administrator to view Cart,Jobs and Sources.
    $this->loginAsAdmin(array('access administration pages'));
    $this->drupalGet('admin');
    $this->clickLink(t('Translation'));
    $this->clickLink(t('Sources'));
    $this->assertText(t('No sources enabled.'));
  }

}
