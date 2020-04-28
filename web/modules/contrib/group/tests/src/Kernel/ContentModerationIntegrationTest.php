<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Symfony\Component\Routing\Route;

/**
 * Tests the overridden access services with additional decorators.
 *
 * @group group
 */
class ContentModerationIntegrationTest extends GroupKernelTestBase {

  use ContentTypeCreationTrait;
  use ContentModerationTestTrait;
  use NodeCreationTrait;

  /**
   * A group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * A group member.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupMember;

  /**
   * A group node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $groupNode;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_moderation',
    'filter',
    'gnode',
    'group_test_content_moderation',
    'node',
    'text',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('node');
    $this->installConfig(['content_moderation', 'filter', 'node', 'text']);
    $this->installSchema('node', ['node_access']);
    $this->createContentType(['type' => 'article']);

    // Create the editorial workflow.
    $this->createEditorialWorkflow();

    // Enable workflow.
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entityTypeManager->getStorage('workflow')->load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'article');
    $workflow->save();

    // Setup the group type.
    $member_permissions = [
      'use editorial transition publish for group_node:article',
      'view latest version for group_node:article',
      'view unpublished group_node:article entity',
    ];
    /** @var \Drupal\group\Entity\GroupTypeInterface $type */
    $type = $this->entityTypeManager->getStorage('group_type')->load('default');
    $type->getMemberRole()->grantPermissions($member_permissions)->save();

    // Enable node content.
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($type, 'group_node:article')->save();

    $this->group = $this->createGroup(['type' => 'default']);
    $this->groupNode = $this->createNode(['type' => 'article']);

    // Add the global permission to create new drafts. This will verify that
    // the content moderation part of the service is still working.
    $this->groupMember = $this->createUser([], ['use editorial transition create_new_draft']);
    $this->group->addContent($this->groupNode, 'group_node:article');
    $this->group->addMember($this->groupMember);
  }

  /**
   * Tests state transition validation.
   */
  public function testStateTransitionValidation() {
    $validator = $this->container->get('content_moderation.state_transition_validation');

    // Verify that all 3 state transitions (one provided by each module in this
    // case) are present.
    // @see \Drupal\group_test_content_moderation\StateTransitionValidation::getValidTransitions
    $states = $validator->getValidTransitions($this->groupNode, $this->groupMember);
    $this->assertCount(3, $states);
    $this->assertArrayHasKey('archive', $states);
    $this->assertArrayHasKey('create_new_draft', $states);
    $this->assertArrayHasKey('publish', $states);
  }

  /**
   * Tests the latest revision access.
   */
  public function testLatestRevisionCheck() {
    $access_check = $this->container->get('access_check.latest_revision');

    // By default there should be no access granted.
    $route = $this->prophesize(Route::class);
    $route->getOption('_content_moderation_entity_type')->willReturn('node');
    // This option is checked by the testing decorator.
    // @see \Drupal\group_test_content_moderation\Access\LatestRevisionCheck::access
    $route->getOption('_explicit_deny')->willReturn(FALSE);
    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route_match->getParameter('node')->willReturn($this->groupNode);

    // The content moderation services is checked here since it will return
    // a forbidden access when no forward revision exists.
    $this->assertInstanceOf(AccessResultForbidden::class, $access_check->access($route->reveal(), $route_match->reveal(), $this->groupMember));

    // Create a forward revision.
    $this->groupNode->moderation_state = 'published';
    $this->groupNode->save();
    $this->groupNode = $this->entityTypeManager->getStorage('node')->loadUnchanged($this->groupNode->id());
    $this->groupNode->setTitle($this->randomString());
    $this->groupNode->moderation_state = 'draft';
    $this->groupNode->save();
    $this->groupNode = $this->entityTypeManager->getStorage('node')->loadUnchanged($this->groupNode->id());
    $route_match->getParameter('node')->willReturn($this->groupNode);

    // The group module should grant access at this point.
    $this->assertInstanceOf(AccessResultAllowed::class, $access_check->access($route->reveal(), $route_match->reveal(), $this->groupMember));

    // The test decorator will no explicitly forbid access.
    // @see \Drupal\group_test_content_moderation\Access\LatestRevisionCheck::access
    $route->getOption('_explicit_deny')->willReturn(TRUE);
    $access = $access_check->access($route->reveal(), $route_match->reveal(), $this->groupMember);
    $this->assertInstanceOf(AccessResultForbidden::class, $access);
    $this->assertEquals('Explicit access denial', $access->getReason());
  }

}
