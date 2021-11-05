<?php

/**
 * @file
 * Definition of Drupal\toc_filter\Tests\TocFilterBlockTest.
 */

namespace Drupal\toc_filter\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests TOC filter block.
 *
 * @group TocFilter
 */
class TocFilterBlockTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'node', 'block', 'filter', 'toc_api', 'toc_filter'];

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A node object.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Setup Filtered HTML text format.
    $html_format = \Drupal::service('entity_type.manager')->getStorage('filter_format')->create([
      'format' => 'html',
      'name' => 'HTML',
      'filters' => [
        'toc_filter' => [
          'status' => TRUE,
        ],
      ],
    ]);

    $html_format->save();

    // Setup a node to test on.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->node = $this->drupalCreateNode();

    // Place TOC filter block.
    $this->drupalPlaceBlock('toc_filter');

    // Setup users.
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'create page content',
      'edit any page content',
      $html_format->getPermissionName(),
    ]);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Test block.
   */
  public function testBlock() {
    $this->node->body->value = '<p>[toc]</p><h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>';
    $this->node->body->format = 'html';
    $this->node->save();

    // Check table of contents displayed inline.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertRaw('<h3>Table of Contents</h3>');
    $this->assertRaw('<a href="#header-2">header 2</a>');
    $this->assertRaw('<a href="#header-3">header 3</a>');
    $this->assertRaw('<a href="#header-4">header 4</a>');
    $this->assertRaw('<a href="#header-4-01">header 4</a>');
    $this->assertRaw('<a href="#header-2-01">header 2</a>');
    $this->assertRaw('<option value="">Table of Contents</option>');
    $this->assertRaw('<option value="#header-2">1) header 2</option>');
    $this->assertRaw('<option value="#header-3">1.1) header 3</option>');
    $this->assertRaw('<option value="#header-4">1.1.1) header 4</option>');
    $this->assertRaw('<option value="#header-4-01">1.1.2) header 4</option>');
    $this->assertRaw('<option value="#header-2-01">2) header 2</option>');
    $this->assertTrue($this->cssSelect('.node__content .toc-tree'));
    $this->assertNoRaw('class="block block-toc-filter"');
    $this->assertNoRaw('<h2>Table of Contents</h2>');

    // Check table of contents displayed in block.
    $this->node->body->value = '<p>[toc block]</p><h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>';
    $this->node->body->format = 'html';
    $this->node->save();
    $this->drupalGet('node/' . $this->node->id());
    $this->assertFalse($this->cssSelect('.node__content .toc-tree'));
    $this->assertRaw('class="block block-toc-filter"');
    $this->assertRaw('<h2>Table of Contents</h2>');
  }

}
