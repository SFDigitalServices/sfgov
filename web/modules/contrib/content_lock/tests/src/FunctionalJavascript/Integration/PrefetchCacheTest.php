<?php

namespace Drupal\Tests\content_lock\FunctionalJavascript\Integration;

use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\content_lock\FunctionalJavascript\ContentLockJavascriptTestBase;

/**
 * Class PrefetchCacheTest.
 *
 * @group content_lock
 */
class PrefetchCacheTest extends ContentLockJavascriptTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'prefetch_cache_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block', ['primary' => TRUE]);
  }

  /**
   * Test the prefetch cache integration with JS locking.
   */
  public function testPrefetchCacheJsLocking() {
    $page = $this->getSession()->getPage();

    $assert_session = $this->assertSession();

    $this->drupalLogin($this->admin);
    $edit = [
      'entity_test_mul_changed[bundles][*]' => 1,
      'entity_test_mul_changed[settings][js_lock]' => 1,
    ];
    $this->drupalPostForm('admin/config/content/content_lock', $edit, t('Save configuration'));
    $lockService = \Drupal::service('content_lock');

    $this->drupalLogin($this->user1);
    $this->drupalGet($this->entity->toUrl());
    // On canonical no lock should be created.
    $this->assertFalse($lockService->fetchLock($this->entity->id(), $this->entity->language()->getId(), NULL, 'entity_test_mul_changed'));

    // On edit page a lock should be there.
    $this->drupalGet($this->entity->toUrl('edit-form'));
    $assert_session->waitForElement('css', 'messages messages--status');
    $this->assertTrue($lockService->fetchLock($this->entity->id(), $this->entity->language()->getId(), NULL, 'entity_test_mul_changed'));

    // After saving, the lock should be gone.
    $page->pressButton(t('Save'));
    $this->assertFalse($lockService->fetchLock($this->entity->id(), $this->entity->language()->getId(), NULL, 'entity_test_mul_changed'));
  }

  /**
   * Test the prefetch cache integration without JS locking.
   */
  public function testPrefetchCacheNormalLocking() {
    $this->drupalLogin($this->admin);
    $edit = [
      'entity_test_mul_changed[bundles][*]' => 1,
      'entity_test_mul_changed[settings][js_lock]' => 0,
    ];
    $this->drupalPostForm('admin/config/content/content_lock', $edit, t('Save configuration'));
    $lockService = \Drupal::service('content_lock');

    $this->drupalLogin($this->user1);
    $this->drupalGet($this->entity->toUrl());

    // Wait a while to give prefetch_cache time to access all pages.
    sleep(3);

    // Without JS locking a lock should be created for the entity.
    $this->assertTrue($lockService->fetchLock($this->entity->id(), $this->entity->language()->getId(), NULL, 'entity_test_mul_changed'));
  }

}
