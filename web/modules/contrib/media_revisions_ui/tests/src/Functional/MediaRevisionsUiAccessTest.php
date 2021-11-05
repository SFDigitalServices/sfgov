<?php

namespace Drupal\Tests\media_revisions_ui\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests media revision pemissions.
 *
 * @group media_revisions_ui
 */
class MediaRevisionsUiAccessTest extends MediaRevisionsTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'node',
    'media',
    'media_test_source',
    'media_revisions_ui',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests access of revision operations.
   */
  public function testOperationsAccess() {
    // Set up two media types.
    $type1 = $this->createMediaType('test');
    $type2 = $this->createMediaType('test');

    // Create a media of first type and load the oldest revision.
    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create([
      'bundle' => $type1->id(),
      'name' => 'Test media 1',
    ]);
    $media->save();
    $this->createMediaRevision($media);
    $mediaRevision = $this->loadOldestRevisionId($media);

    // Create a media of second type and load the oldest revision.
    /** @var \Drupal\media\MediaInterface $media2 */
    $media2 = Media::create([
      'bundle' => $type2->id(),
      'name' => 'Test media 2',
    ]);
    $media2->save();
    $this->createMediaRevision($media2);
    $media2Revision = $this->loadOldestRevisionId($media2);

    // Test that the user can view, revert or delete any revision with
    // administer media permission.
    $this->createUserWithPermissionsAndLogin([
      'administer media',
    ]);
    $this->assertRevisionViewStatusCode($mediaRevision, 200);
    $this->assertRevisionRevertStatusCode($mediaRevision, 200);
    $this->assertRevisionDeleteStatusCode($mediaRevision, 200);

    $this->assertRevisionViewStatusCode($media2Revision, 200);
    $this->assertRevisionRevertStatusCode($media2Revision, 200);
    $this->assertRevisionDeleteStatusCode($media2Revision, 200);

    // Test that the user can only view all revisions but not revert or delete.
    $this->createUserWithPermissionsAndLogin([
      'view all media revisions',
    ]);

    $this->assertRevisionViewStatusCode($mediaRevision, 200);
    $this->assertRevisionRevertStatusCode($mediaRevision, 403);
    $this->assertRevisionDeleteStatusCode($mediaRevision, 403);

    $this->assertRevisionViewStatusCode($media2Revision, 200);
    $this->assertRevisionRevertStatusCode($media2Revision, 403);
    $this->assertRevisionDeleteStatusCode($media2Revision, 403);

    // Test that the user can only revert all revisions but not view or delete.
    $this->createUserWithPermissionsAndLogin([
      'update any media',
      'revert all media revisions',
    ]);

    $this->assertRevisionViewStatusCode($mediaRevision, 403);
    $this->assertRevisionRevertStatusCode($mediaRevision, 200);
    $this->assertRevisionDeleteStatusCode($mediaRevision, 403);

    $this->assertRevisionViewStatusCode($media2Revision, 403);
    $this->assertRevisionRevertStatusCode($media2Revision, 200);
    $this->assertRevisionDeleteStatusCode($media2Revision, 403);

    // Test that the user can only delete all revisions but not view or revert.
    $this->createUserWithPermissionsAndLogin([
      'delete any media',
      'delete all media revisions',
    ]);

    $this->assertRevisionViewStatusCode($mediaRevision, 403);
    $this->assertRevisionRevertStatusCode($mediaRevision, 403);
    $this->assertRevisionDeleteStatusCode($mediaRevision, 200);

    $this->assertRevisionViewStatusCode($media2Revision, 403);
    $this->assertRevisionRevertStatusCode($media2Revision, 403);
    $this->assertRevisionDeleteStatusCode($media2Revision, 200);

    // Test individual permissions on media type.
    $this->createUserWithPermissionsAndLogin([
      "view {$type1->id()} media revisions",
    ]);

    $this->assertRevisionViewStatusCode($mediaRevision, 200);
    $this->assertRevisionRevertStatusCode($mediaRevision, 403);
    $this->assertRevisionDeleteStatusCode($mediaRevision, 403);
    $this->assertRevisionViewStatusCode($media2Revision, 403);
    $this->assertRevisionRevertStatusCode($media2Revision, 403);
    $this->assertRevisionDeleteStatusCode($media2Revision, 403);

    $this->createUserWithPermissionsAndLogin([
      "update any media",
      "revert {$type1->id()} media revisions",
    ]);

    $this->assertRevisionViewStatusCode($mediaRevision, 403);
    $this->assertRevisionRevertStatusCode($mediaRevision, 200);
    $this->assertRevisionDeleteStatusCode($mediaRevision, 403);
    $this->assertRevisionViewStatusCode($media2Revision, 403);
    $this->assertRevisionRevertStatusCode($media2Revision, 403);
    $this->assertRevisionDeleteStatusCode($media2Revision, 403);

    $this->createUserWithPermissionsAndLogin([
      "delete any media",
      "delete {$type1->id()} media revisions",
    ]);

    $this->assertRevisionViewStatusCode($mediaRevision, 403);
    $this->assertRevisionRevertStatusCode($mediaRevision, 403);
    $this->assertRevisionDeleteStatusCode($mediaRevision, 200);
    $this->assertRevisionViewStatusCode($media2Revision, 403);
    $this->assertRevisionRevertStatusCode($media2Revision, 403);
    $this->assertRevisionDeleteStatusCode($media2Revision, 403);

    // Test that revert media revision does not work without update media
    // permission.
    $this->createUserWithPermissionsAndLogin([
      "revert {$type1->id()} media revisions",
    ]);
    $this->assertRevisionRevertStatusCode($mediaRevision, 403);

    // Test that delete media revision does not work without delete media
    // permission.
    $this->createUserWithPermissionsAndLogin([
      "delete {$type1->id()} media revisions",
    ]);
    $this->assertRevisionDeleteStatusCode($mediaRevision, 403);

    // Test that revert media revision does not work without update media
    // revisions permission.
    $this->createUserWithPermissionsAndLogin([
      "update any media",
    ]);
    $this->assertRevisionRevertStatusCode($mediaRevision, 403);

    // Test that delete media revision does not work without delete media
    // revisions permission.
    $this->createUserWithPermissionsAndLogin([
      "delete any media",
    ]);
    $this->assertRevisionDeleteStatusCode($mediaRevision, 403);
  }

  /**
   * Visits media revision view page and asserts status code.
   *
   * @param \Drupal\Core\Entity\EntityInterface $media
   *   The media entity.
   * @param int $expectedStatusCode
   *   Expected status code when visiting revision view page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertRevisionViewStatusCode(EntityInterface $media, $expectedStatusCode) {
    $this->drupalGet("/media/{$media->id()}/revisions/{$media->getRevisionId()}/view");
    $this->assertSession()->statusCodeEquals($expectedStatusCode);
  }

  /**
   * Visits media revision revert page and asserts status code.
   *
   * @param \Drupal\Core\Entity\EntityInterface $media
   *   The media entity.
   * @param int $expectedStatusCode
   *   Expected status code when visiting revision revert page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertRevisionRevertStatusCode(EntityInterface $media, $expectedStatusCode) {
    $this->drupalGet("/media/{$media->id()}/revisions/{$media->getRevisionId()}/revert");
    $this->assertSession()->statusCodeEquals($expectedStatusCode);
  }

  /**
   * Visits media revision delete page and asserts status code.
   *
   * @param \Drupal\Core\Entity\EntityInterface $media
   *   The media entity.
   * @param int $expectedStatusCode
   *   Expected status code when visiting revisions list.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertRevisionDeleteStatusCode(EntityInterface $media, $expectedStatusCode) {
    $this->drupalGet("/media/{$media->id()}/revisions/{$media->getRevisionId()}/delete");
    $this->assertSession()->statusCodeEquals($expectedStatusCode);
  }

}
