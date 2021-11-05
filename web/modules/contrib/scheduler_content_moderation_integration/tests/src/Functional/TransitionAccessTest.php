<?php

namespace Drupal\Tests\scheduler_content_moderation_integration\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Test covering the TransitionAccessConstraintValidator.
 *
 * @coversDefaultClass \Drupal\scheduler_content_moderation_integration\Plugin\Validation\Constraint\TransitionAccessConstraintValidator
 *
 * @group scheduler
 */
class TransitionAccessTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_moderation', 'scheduler_content_moderation_integration'];

  /**
   * User.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ])
      ->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

    $this->schedulerUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'edit any page content',
      'schedule publishing of nodes',
      'view latest version',
      'view any unpublished content',
      'access content overview',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'use editorial transition archive',
    ]);

    $this->restrictedUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'edit own page content',
      'view latest version',
      'view any unpublished content',
      'access content overview',
      'use editorial transition create_new_draft',
    ]);

  }

  /**
   * Test TransitionAccessConstraintValidator.
   */
  public function testTransitionAccess() {
    $this->drupalLogin($this->schedulerUser);

    // Create a node and publish it using the "publish" transition.
    $edit = [
      'title[0][value]' => $this->randomString(),
      'moderation_state[0][state]' => 'published',
    ];
    $this->drupalPostForm('node/add/page', $edit, 'Save');

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $publish_time = strtotime('+2 days');

    // Change node moderation state to "archived" (using the "archive"
    // transition), and schedule publishing.
    $edit = [
      'moderation_state[0][state]' => 'archived',
      'publish_on[0][value][date]' => date('Y-m-d', $publish_time),
      'publish_on[0][value][time]' => date('H:i:s', $publish_time),
      'publish_state[0]' => 'published',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, 'Save');
    // It should fail because the user does not have access to the
    // "archived_published" transition.
    $this->assertSession()->pageTextContains('You do not have access to transition from Archived to Published');

    // Ensure that allowed transitions can still be used (the "publish" one).
    $edit = [
      'moderation_state[0][state]' => 'draft',
      'publish_on[0][value][date]' => date('Y-m-d', $publish_time),
      'publish_on[0][value][time]' => date('H:i:s', $publish_time),
      'publish_state[0]' => 'published',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, 'Save');
    $date_formatter = \Drupal::service('date.formatter');
    $this->assertSession()->pageTextContains(sprintf('%s is scheduled to be published %s.', $node->getTitle(), $date_formatter->format($publish_time, 'long')));
  }

  /**
   * Test access to scheduled content for users without right to transition.
   */
  public function testRestrictedTransitionAccess() {
    // Create a draft as restricted user.
    $this->drupalLogin($this->restrictedUser);
    $edit = [
      'title[0][value]' => $this->randomString(),
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/add/page', $edit, 'Save');

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $publish_time = strtotime('+2 days');
    $date_formatter = \Drupal::service('date.formatter');

    // Schedule publishing.
    $this->drupalLogin($this->schedulerUser);
    $edit = [
      'moderation_state[0][state]' => 'draft',
      'publish_on[0][value][date]' => date('Y-m-d', $publish_time),
      'publish_on[0][value][time]' => date('H:i:s', $publish_time),
      'publish_state[0]' => 'published',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, 'Save');

    $this->assertSession()
      ->pageTextContains(sprintf('%s is scheduled to be published %s.', $node->getTitle(), $date_formatter->format($publish_time, 'long')));

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertResponse(200, 'Scheduler user should be able to edit the node."');

    // Restricted user does not have permission to scheduled transition,
    // editing access should be denied.
    $this->drupalLogin($this->restrictedUser);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertResponse(403, 'Restricted user should not be able to edit the node."');

    // Remove scheduling info.
    $this->drupalLogin($this->schedulerUser);
    $edit = [
      'moderation_state[0][state]' => 'draft',
      'publish_on[0][value][date]' => NULL,
      'publish_on[0][value][time]' => NULL,
      'publish_state[0]' => '_none',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, 'Save');

    // Check if node is editable when there is no scheduling
    // (using 'create_new_draft' transition).
    $this->drupalLogin($this->restrictedUser);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertResponse(200, 'Restricted user should be able to edit the node."');
  }

}
