<?php

namespace Drupal\Tests\media_revisions_ui\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests media revisions UI.
 *
 * @group media_revisions_ui
 */
class MediaRevisionsUiTest extends BrowserTestBase {

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
  protected $defaultTheme = 'stark';

  /**
   * User to test media revisions tab.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Media type entity.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $mediaType;

  /**
   * Media storage.
   *
   * @var \Drupal\media\MediaStorage
   */
  protected $mediaStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $this->container->get('entity_type.manager');
    $this->mediaStorage = $entityTypeManager->getStorage('media');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->mediaType = $this->createMediaType('test');
  }

  /**
   * Tests access to media revisions tab.
   */
  public function testTabAccess() {
    $media = Media::create([
      'bundle' => $this->mediaType->id(),
      'name' => 'Test media',
    ]);
    $media->save();
    // Test that access is denied without permissions.
    $this->assertRevisionsListStatusCode(
      $this->drupalCreateUser([]),
      $media,
      403
    );
    $user = $this->drupalCreateUser([
      'administer media',
      'view all media revisions',
    ]);

    // Test that access is denied if a media type does not create revisions
    // by default and media has just been created.
    $media = Media::create([
      'bundle' => $this->mediaType->id(),
      'name' => 'Test media',
    ]);
    $media->save();
    $this->assertRevisionsListStatusCode($user, $media, 403);
    $this->assertRevisionsTabNotExists($media);

    // Test that access is allowed if a media revision is created.
    $this->createMediaRevision($media);
    $this->assertRevisionsListStatusCode($user, $media, 200);
    $this->assertRevisionsTabExists($media);

    // Delete revision and check that revisions list is not accessible anymore.
    $revisionId = $this->loadOldestRevisionId($media);
    $this->mediaStorage->deleteRevision($revisionId);
    $this->assertRevisionsListStatusCode($user, $media, 403);
    $this->assertRevisionsTabNotExists($media);

    // Test that access is allowed if a media type does create revisions
    // by default and media has just been created.
    $this->mediaType->setNewRevision(TRUE);
    $this->mediaType->save();
    $media = Media::create([
      'bundle' => $this->mediaType->id(),
      'name' => 'Test media',
    ]);
    $media->save();
    $this->assertRevisionsListStatusCode($user, $media, 200);
    $this->assertRevisionsTabExists($media);
  }

  /**
   * Tests reverting a revision.
   */
  public function testRevert() {
    $media = Media::create([
      'bundle' => $this->mediaType->id(),
      'name' => 'Test media',
    ]);
    $media->save();
    $user = $this->drupalCreateUser([
      'administer media',
      'view all media revisions',
    ]);
    $this->createMediaRevision($media);
    $this->assertRevisionsListStatusCode($user, $media, 200);
    $this->clickLink('Revert');
    $this->assertSession()->statusCodeEquals(200);
    $this->getSession()->getPage()->pressButton('Revert');
    $this->assertSession()->pageTextContains('Media Test media has been reverted');
  }

  /**
   * Creates a new revision for a given media item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $media
   *   Media to create revision in.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A media object with up to date revision information.
   */
  protected function createMediaRevision(EntityInterface $media) {
    $media->setName($this->randomMachineName());
    $media->setNewRevision();
    $media->save();

    return $media;
  }

  /**
   * Logs in a user, visits media revisions list page and asserts status code.
   *
   * @param \Drupal\user\Entity\User $user
   *   User to log in.
   * @param \Drupal\Core\Entity\EntityInterface $media
   *   Media from which to load revisions list.
   * @param int $expectedStatusCode
   *   Expected status code when visiting revisions list.
   */
  protected function assertRevisionsListStatusCode(User $user, EntityInterface $media, $expectedStatusCode) {
    $this->drupalLogin($user);
    $this->drupalGet("/media/{$media->id()}/revisions");
    $this->assertSession()->statusCodeEquals($expectedStatusCode);
  }

  /**
   * Asserts that "Revisions" tab does not exist on edit page.
   *
   * @param \Drupal\Core\Entity\EntityInterface $media
   *   Media to visit edit page.
   */
  protected function assertRevisionsTabNotExists(EntityInterface $media) {
    $this->drupalGet("/media/{$media->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->linkByHrefNotExists("/media/{$media->id()}/revisions");
  }

  /**
   * Asserts that "Revisions" tab does exists on edit page.
   *
   * @param \Drupal\Core\Entity\EntityInterface $media
   *   Media to visit edit page.
   */
  protected function assertRevisionsTabExists(EntityInterface $media) {
    $this->drupalGet("/media/{$media->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this
      ->assertSession()
      ->linkByHrefExists("/media/{$media->id()}/revisions");
  }

  /**
   * Loads oldest revision id from media.
   *
   * @param \Drupal\Core\Entity\EntityInterface $media
   *   Media to load revision.
   *
   * @return int|null
   *   Returns revision id or NULL if not found.
   */
  protected function loadOldestRevisionId(EntityInterface $media) {
    $result = $this->mediaStorage->getQuery()
      ->allRevisions()
      ->condition('mid', $media->id())
      ->sort('vid', 'ASC')
      ->range(NULL, 1)
      ->execute();

    return array_keys($result) ? array_keys($result)[0] : NULL;
  }

}
