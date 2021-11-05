<?php

namespace Drupal\Tests\viewfield\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Entity\View;

/**
 * Provide basic setup for all Viewfield functional tests.
 *
 * @group viewfield
 */
abstract class ViewfieldFunctionalTestBase extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'field',
    'node',
    'views',
    'views_ui',
    'viewfield',
  ];

  /**
   * Set default theme.
   */
  protected $defaultTheme = 'stark';

  /**
   * The Entity View Display for the article_test node type.
   *
   * @var \Drupal\Core\Entity\Entity\EntityViewDisplay
   */
  protected $display;

  /**
   * The Entity Form Display for the article_test node type.
   *
   * @var \Drupal\Core\Entity\Entity\EntityFormDisplay
   */
  protected $form;

  /**
   * A user with all permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article_test']);
    $this->drupalCreateContentType(['type' => 'page_test']);
    $user = $this->drupalCreateUser(['create article_test content', 'edit own article_test content']);
    $this->drupalLogin($user);
    $entityTypeManager = $this->container->get('entity_type.manager');
    FieldStorageConfig::create([
      'field_name' => 'field_view_test',
      'entity_type' => 'node',
      'type' => 'viewfield',
      'settings' => [
        'target_type' => 'view',
      ],
      'module' => 'viewfield',
      'cardinality' => -1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_view_test',
      'label' => 'Viewfield',
      'description' => 'Viewfield description',
      'entity_type' => 'node',
      'bundle' => 'article_test',
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [],
      ],
    ])->save();
    $this->form = $entityTypeManager->getStorage('entity_form_display')
      ->load('node.article_test.default');
    $this->display = $entityTypeManager->getStorage('entity_view_display')
      ->load('node.article_test.default');

    // Create content for views to display.
    for ($i = 1; $i <= 3; $i++) {
      $this->createNode([
        'title' => 'Page ' . $i,
        'status' => TRUE,
        'type' => 'page_test',
      ]);
      $this->createNode([
        'title' => 'Article ' . $i,
        'status' => TRUE,
        'type' => 'article_test',
      ]);
    }

    // Create view.
    $this->createView(50);

  }

  /**
   * Create a view setup for testing Viewfield.
   */
  protected function createView($items_per_page = 3) {
    View::create([
      'label' => 'Content Test',
      'id' => 'content_test',
      'base_table' => 'node_field_data',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_options' => [
            'row' => [
              'type' => 'entity:node',
              'options' => [
                'view_mode' => 'teaser',
              ],
            ],
            'pager' => [
              'type' => 'full',
              'options' => [
                'items_per_page' => $items_per_page,
                'offset' => 0,
              ],
            ],
            'use_ajax' => TRUE,
            'arguments' => [
              'type' => [
                'id' => 'type',
                'table' => 'node_field_data',
                'field' => 'type',
                'relationship' => 'none',
                'group_type' => 'group',
                'default_action' => 'ignore',
                'exception' => [
                  'value' => 'all',
                ],
                'default_argument_type' => 'fixed',
                'entity_type' => 'node',
                'entity_field' => 'type',
                'plugin_id' => 'node_type',
              ],
            ],
          ],
        ],
        'block_1' => [
          'display_plugin' => 'block',
          'id' => 'block_1',
        ],
      ],
    ])->save();
    \Drupal::service('router.builder')->rebuild();
  }


}
