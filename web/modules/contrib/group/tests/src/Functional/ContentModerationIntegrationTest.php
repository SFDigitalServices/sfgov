<?php

namespace Drupal\Tests\group\Functional;

use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests integration with the core Content Moderation module.
 *
 * @group group
 */
class ContentModerationIntegrationTest extends GroupBrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * A group for testing purposes.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * A normal group member.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupMember;

  /**
   * A non-group member.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonGroupMember;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_moderation',
    // @todo Ideally this would test with a non-node content enabler.
    'gnode',
    'group',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create the editorial workflow.
    $this->createEditorialWorkflow();

    // Set permissions for content moderation in the default group type.
    $member_permissions = [
      'update any group_node:article entity',
      'use editorial transition publish for group_node:article',
      'view unpublished group_node:article entity',
      'view latest version for group_node:article',
    ];

    /** @var \Drupal\group\Entity\GroupTypeInterface $type */
    $type = $this->entityTypeManager->getStorage('group_type')->load('default');
    $type->getMemberRole()->grantPermissions($member_permissions)->save();

    // Add the article content type to the group type, and enable workflow.
    $this->createContentType(['type' => 'article']);
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($type, 'group_node:article')->save();
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entityTypeManager->getStorage('workflow')->load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'article');
    $workflow->save();

    // Add a group.
    $this->group = $this->createGroup();

    $this->nonGroupMember = $this->createUser();
    $this->groupMember = $this->createUser([
      // Utilize a global permission here to ensure those are merged in the
      // access decorator.
      'use editorial transition create_new_draft',
    ]);

    $this->group->addMember($this->groupMember);

    node_access_rebuild();
  }

  /**
   * Tests access to the latest version tab of non group nodes.
   *
   * This is a basic sanity check to ensure the logic in group is not changing
   * the behavior of content moderation for non-group entities.
   */
  public function testLatestVersionAccessNonGroupNode() {
    $node = $this->createNode(['type' => 'article']);

    // A non-member should not have access to this draft state.
    $this->drupalLogin($this->nonGroupMember);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(403);

    // The group member should not have access.
    $this->drupalLogin($this->groupMember);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests access for group nodes.
   */
  public function testLatestVersionAccessGroupNode() {
    $node = $this->createNode(['type' => 'article']);
    $this->group->addContent($node, 'group_node:article');

    // A non-member should not have access to this draft state.
    $this->drupalLogin($this->nonGroupMember);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(403);

    // The group member should have access.
    $this->drupalLogin($this->groupMember);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);

    // Create a forward revision and ensure access to that as well.
    $edit = [
      'moderation_state[0][state]' => 'published',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet($node->toUrl('edit-form'));
    $this->drupalPostForm(NULL, ['title[0][value]' => 'New draft'], t('Save'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
