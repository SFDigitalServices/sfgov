<?php
/**
 * Tests for the SFgovDepartment class.
 *
 * @group sfgov_departments
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

namespace Drupal\Tests\sfgov_departments\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\sfgov_departments\SFgovDepartment;
use Drupal\Tests\BrowserTestBase;

class SFgovDepartmentTest extends BrowserTestBase {

  use EntityReferenceTestTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Enabled modules
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'node',
    'group',
    'gnode',
    'sfgov_departments',
  ];

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Create content types.
    $node_type = NodeType::create([
      'type' => 'department',
      'name' => 'Department',
      'description' => "",
    ]);
    $node_type->save();
    $node_type = NodeType::create([
      'type' => 'transaction',
      'name' => 'Transaction',
      'description' => "",
    ]);
    $node_type->save();

    // Create a field_departments in transaction content type.
    $this->createEntityReferenceField(
      'node',
      'transaction',
      'field_departments',
      'Departments',
      'node',
      'default',
      ['target_bundles' => ['department']],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    // Create group type.
    $group_type = $this->entityTypeManager->getStorage('group_type')->create([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'department',
      'label' => 'Department',
      'description' => '',
      'creator_membership' => TRUE,
      'creator_wizard' => FALSE,
      'creator_roles' => [],
      'dependencies' => [],
    ]);
    $group_type->enforceIsNew();
    $group_type->save();

    // Create a field_department in department group type.
    $this->createEntityReferenceField(
      'group',
      'department',
      'field_department',
      'Department',
      'node',
      'default',
      ['target_bundles' => ['department']],
      1
    );

    // Install some node types on the department group types.
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($group_type, 'group_node:department')->save();
    $storage->createFromPlugin($group_type, 'group_node:transaction')->save();
  }

  /**
   * Test the SFgovDepartment class.
   */
  public function testSFgovDepartment() {
    // Create a department node.
    $department_node = Node::create([
      'type' => 'department',
      'title' => 'Department A',
      'status' => TRUE,
    ]);
    $department_node->save();
    $this->assertTrue(is_numeric($department_node->id()) && ($department_node->id() > 0), 'Department node created with nid: ' . $department_node->id());

    // Test that a department group got indeed created for it.
    $sfgov_department = new SFgovDepartment($department_node);
    $group = $sfgov_department->getDepartmentGroup();
    $this->assertEquals($group->bundle(), 'department', 'Department group created for department node.');

    // Test that the deparment group correctly references the department node.
    $deps = $group->field_department->referencedEntities();
    $department_backreference_nid = empty($deps) ? NULL : reset($deps)->id();
    $this->assertEquals($department_backreference_nid, $department_node->id(), 'Department group correctly references department node.');

    // Create a transaction node.
    $transaction_node = Node::create([
      'type' => 'transaction',
      'title' => 'Transaction 1',
      'status' => TRUE,
      'field_departments' => [
        ['target_id' => $department_node->id()],
      ],
    ]);
    $transaction_node->save();

    // Check that node got added to group.
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin('group_node:transaction');
    $query = \Drupal::entityQuery('group_content')
      ->condition('type', $plugin->getContentTypeConfigId())
      ->condition('gid', $group->id())
      ->condition('entity_id', $transaction_node->id())
      ->range(0, 1);
    $ids = $query->execute();
    $this->assertFalse(empty($ids), 'Transaction node got added to department group.');

    // Test if department group gets deleted after deleting department node.
    // Need to clear storage caches first.
    \Drupal::entityManager()->getStorage('node')->resetCache([$department_node->id(), $transaction_node->id()]);
    \Drupal::entityManager()->getStorage('group')->resetCache([$group->id()]);
    \Drupal::entityManager()->getStorage('group_content')->resetCache();
    $department_node->delete();
    $group_reloaded = $this->entityTypeManager->getStorage('group')->load($group->id());
    $this->assertTrue(empty($group_reloaded), 'Related department group no longer exists.');
  }

}
