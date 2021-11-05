<?php

namespace Drupal\Tests\scheduler_content_moderation_integration\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Test if layout builder can be accessed.
 *
 * @see https://www.drupal.org/project/scheduler_content_moderation_integration/issues/3048485
 * @group scheduler
 */
class LayoutBuilderTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'content_moderation',
    'scheduler_content_moderation_integration',
    'layout_builder',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article'])
      ->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'article');
    $workflow->save();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'access content',
      'administer node display',
    ]));
  }

  /**
   * Tests layout builder.
   */
  public function testLayoutBuilder() {
    $path = 'admin/structure/types/manage/article/display/default';

    $page = $this->getSession()->getPage();
    $this->drupalGet($path);
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');

    $this->drupalGet(Url::fromRoute('layout_builder.defaults.node.view', ['node_type' => 'article', 'view_mode_name' => 'default']));
    $this->assertSession()->statusCodeEquals(200);
  }

}
