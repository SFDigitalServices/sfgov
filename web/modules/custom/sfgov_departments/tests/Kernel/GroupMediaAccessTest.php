<?php

namespace Drupal\Tests\sfgov_departments\Kernel;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\group\Entity\Access\GroupContentAccessControlHandler;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\media\Entity\MediaType;
use Drupal\user\RoleInterface;

/**
 * Test base for testing access records and grants for group media.
 */
class GroupMediaAccessTest extends EntityKernelTestBase {

  /**
   * Enabled modules
   *
   * @var array
   */
  public static $modules = [
    'file',
    'image',
    'media',
    'group',
    'sfgov_departments',
  ];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * The account to use for testing authenticated.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The account to use for testing anonymous.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anonymous;

  /**
   * A dummy group type with ID 'a'.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeA;

  /**
   * A dummy group type with ID 'b'.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeB;

  /**
   * A dummy group of type 'a' with the test account as a member.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupA1;

  /**
   * A dummy group of type 'a' with the test account as an outsider.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupA2;

  /**
   * A dummy group of type 'b' with the test account as a member.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupB1;

  /**
   * A dummy group of type 'b' with the test account as an outsider.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupB2;

  /**
   * A dummy media entity of type 'a'.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $mediaEntity1;

  /**
   * A dummy media entity of type 'a'.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $mediaEntity2;

  /**
   * A dummy media entity of type 'a'.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $mediaEntity3;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->pluginManager = $this->container->get('plugin.manager.group_content_enabler');

    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installEntitySchema('media');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');
    $this->installConfig(['field', 'system', 'file', 'image']);
    $this->installConfig(['group', 'media']);

    // Allow anonymous role to view any media.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, [
      'view media',
    ]);

    // Allow authenticated to view, update and delete any media.
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, [
      'view media',
      'update media',
      'update any media',
      'delete media',
      'delete any media',
    ]);

    // Create the test users accounts.
    $this->anonymous = $this->createUser(['uid' => 0]);
    $this->account = $this->createUser(['uid' => 2], [
      'view media',
      'update media',
      'delete media',
    ]);
    $other_account = $this->createUser(['uid' => 3]);

    // Create some group types.
    $storage = $this->entityTypeManager->getStorage('group_type');
    $values = ['label' => 'foo', 'description' => 'bar'];
    $this->groupTypeA = $storage->create(['id' => 'a'] + $values);
    $this->groupTypeA->save();

    // Create some media types.
    $this->createMediaType('a');

    // Create a couple of media entities.
    $this->mediaEntity1 = Media::create([
      'bundle' => 'a',
      'name' => $this->randomMachineName(),
      'uid' => 1,
    ]);
    $this->mediaEntity1->save();
    $this->mediaEntity2 = Media::create([
      'bundle' => 'a',
      'name' => $this->randomMachineName(),
      'uid' => 1,
    ]);
    $this->mediaEntity2->save();
    $this->mediaEntity3 = Media::create([
      'bundle' => 'a',
      'name' => $this->randomMachineName(),
      'uid' => $this->account->id(),
    ]);
    $this->mediaEntity3->save();

    // Install some media types on some group types.
    $this->pluginManager->clearCachedDefinitions();
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($this->groupTypeA, 'group_media:a')->save();

    // Set group_media permissions on the group types.
    $member_a = [
      'view group_media:a entity',
      'update any group_media:a entity',
      'delete any group_media:a entity',
      'update own group_media:a entity',
      'delete own group_media:a entity',
    ];
    $outsider_a = [
      'view group_media:a entity',
      'update own group_media:a entity',
      'delete own group_media:a entity',
    ];
    $anonymous_a = [
      'view group_media:a entity',
    ];
    $this->groupTypeA->getMemberRole()->grantPermissions($member_a)->save();
    $this->groupTypeA->getOutsiderRole()->grantPermissions($outsider_a)->save();
    $this->groupTypeA->getAnonymousRole()->grantPermissions($anonymous_a)->save();

    // Create some groups.
    $storage = $this->entityTypeManager->getStorage('group');
    $values = ['uid' => $other_account->id(), 'label' => 'foo'];
    $this->groupA1 = $storage->create(['type' => 'a'] + $values);
    $this->groupA2 = $storage->create(['type' => 'a'] + $values);
    $this->groupA1->save();
    $this->groupA2->save();

    // Add media entities to different groups.
    $this->groupA1->addContent($this->mediaEntity1, 'group_media:a');
    $this->groupA2->addContent($this->mediaEntity2, 'group_media:a');
    $this->groupA1->addContent($this->mediaEntity3, 'group_media:a');

    // Remove the test account from the A2 and B2 groups.
    $this->groupA2->removeMember($this->account);

    // Need to clear storage caches first.
    $this->clearCaches();
  }

  /**
   * Tests that a user receives the right permissions for group nodes.
   */
  public function testPermissions() {
    $handler = $this->entityManager->getAccessControlHandler('media');

    // Test anonymous permissions.
    $view = $handler->access($this->mediaEntity1, 'view', $this->anonymous, TRUE);
    $update = $handler->access($this->mediaEntity1, 'update', $this->anonymous, TRUE);
    $delete = $handler->access($this->mediaEntity1, 'delete', $this->anonymous, TRUE);
    $this->assertTrue($view instanceof AccessResultAllowed,
      '1 Anonymous should be able to view media A.');
    $this->assertTrue($update instanceof AccessResultNeutral,
      '2 Anonymous should be able to update media A depending on "user.permissions".');
    $this->assertTrue($delete instanceof AccessResultNeutral,
      '3 Anonymous should be able to delete media A depending on "user.permissions".');

    // Test as member of group A1.
    $this->groupA1->addMember($this->account);
    $this->clearCaches();
    $view = $handler->access($this->mediaEntity1, 'view', $this->account, TRUE);
    $update = $handler->access($this->mediaEntity1, 'update', $this->account, TRUE);
    $delete = $handler->access($this->mediaEntity1, 'delete', $this->account, TRUE);
    $this->assertTrue($view instanceof AccessResultAllowed,
      '4 Member should be able to view media A.');
    $this->assertTrue($update instanceof AccessResultAllowed,
      '5 Member should be able to update media A.');
    $this->assertTrue($delete instanceof AccessResultAllowed,
      '6 Member should be able to delete media A.');

    // Test outsider permissions.
    $view = $handler->access($this->mediaEntity2, 'view', $this->account, TRUE);
    $update = $handler->access($this->mediaEntity2, 'update', $this->account, TRUE);
    $delete = $handler->access($this->mediaEntity2, 'delete', $this->account, TRUE);
    $this->assertTrue($view instanceof AccessResultAllowed,
      '7 Outsider should be able to view media A.');
    $this->assertTrue($update instanceof AccessResultNeutral,
      '8 Outsider should be able to update media A depending on "user.permissions"');
    $this->assertTrue($delete instanceof AccessResultNeutral,
      '9 Outsider should be able to delete media A depending on "user.permissions"');

    // Test as author.
    $view = $handler->access($this->mediaEntity3, 'view', $this->account, TRUE);
    $update = $handler->access($this->mediaEntity3, 'update', $this->account, TRUE);
    $delete = $handler->access($this->mediaEntity3, 'delete', $this->account, TRUE);
    $this->assertTrue($view instanceof AccessResultAllowed,
      '10 Author should be able to view media A.');
    $this->assertTrue($update instanceof AccessResultAllowed,
      '11 Author should be able to update media A.');
    $this->assertTrue($delete instanceof AccessResultAllowed,
      '12 Author should be able to delete media A.');
  }

  protected function createMediaType($id) {
    $media_type = MediaType::create([
      'id' => $id,
      'label' => $id,
      'source' => 'file',
      'new_revision' => FALSE,
    ]);

    $media_type->save();

    $source_field = $media_type->getSource()->createSourceField($media_type);
    // The media type form creates a source field if it does not exist yet. The
    // same must be done in a kernel test, since it does not use that form.
    // @see \Drupal\media\MediaTypeForm::save()
    $source_field->getFieldStorageDefinition()->save();
    // The source field storage has been created, now the field can be saved.
    $source_field->save();
    $media_type->set('source_configuration', [
      'source_field' => $source_field->getName(),
    ])->save();

    return $media_type;
  }

  /**
   * Grant permissions to a user role.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The ID of a user role to alter.
   * @param array $permissions
   *   (optional) A list of permission names to grant.
   */
  protected function grantPermissions(RoleInterface $role, array $permissions) {
    foreach ($permissions as $permission) {
      $role->grantPermission($permission);
    }
    $role->trustData()->save();
  }

  protected function clearCaches() {
    $this->entityManager->getStorage('user')->resetCache();
    $this->entityManager->getStorage('media')->resetCache();
    $this->entityManager->getStorage('group')->resetCache();
    $this->entityManager->getStorage('group_content')->resetCache();
  }

}
