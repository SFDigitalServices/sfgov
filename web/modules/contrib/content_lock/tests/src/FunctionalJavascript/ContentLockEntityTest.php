<?php

namespace Drupal\Tests\content_lock\FunctionalJavascript;

/**
 * Class ContentLockEntityTest.
 *
 * @group content_lock
 */
class ContentLockEntityTest extends ContentLockJavascriptTestBase {

  /**
   * Test JS locking.
   */
  public function testJsLocking() {

    $this->drupalLogin($this->admin);
    $edit = [
      'entity_test_mul_changed[bundles][*]' => 1,
      'entity_test_mul_changed[settings][js_lock]' => 1,
    ];
    $this->drupalPostForm('admin/config/content/content_lock', $edit, t('Save configuration'));
    $page = $this->getSession()->getPage();

    // We lock entity.
    $this->drupalLogin($this->user1);
    // Edit a entity without saving.
    $this->drupalGet($this->entity->toUrl('edit-form'));
    $assert_session = $this->assertSession();
    $assert_session->waitForElement('css', 'messages messages--status');
    $assert_session->pageTextContains(t('This content is now locked against simultaneous editing.'));

    // Other user can not edit entity.
    $this->drupalLogin($this->user2);
    $this->drupalGet($this->entity->toUrl('edit-form'));
    $assert_session->waitForElement('css', 'messages messages--status');
    $assert_session->pageTextContains(t('This content is being edited by the user @name and is therefore locked to prevent other users changes.', [
      '@name' => $this->user1->getDisplayName(),
    ]));
    $assert_session->linkExists(t('Break lock'));
    $disabled_button = $assert_session->elementExists('css', 'input[disabled][data-drupal-selector="edit-submit"]');
    $this->assertTrue($disabled_button, t('The form cannot be submitted.'));
    $disabled_field = $this->xpath('//input[@id=:id and @disabled]', [':id' => 'edit-field-test-text-0-value']);
    $this->assertTrue($disabled_field, t('The form cannot be submitted.'));

    // We save entity 1 and unlock it.
    $this->drupalLogin($this->user1);
    $this->drupalGet($this->entity->toUrl('edit-form'));
    $assert_session->waitForElement('css', 'messages messages--status');
    $assert_session->pageTextContains(t('This content is now locked by you against simultaneous editing.'));
    $page->pressButton(t('Save'));

    // We lock entity with user2.
    $this->drupalLogin($this->user2);
    // Edit a entity without saving.
    $this->drupalGet($this->entity->toUrl('edit-form'));
    $assert_session->waitForElement('css', 'messages messages--status');
    $assert_session->pageTextContains(t('This content is now locked against simultaneous editing.'));

    // Other user can not edit entity.
    $this->drupalLogin($this->user1);
    $this->drupalGet($this->entity->toUrl('edit-form'));
    $assert_session->waitForElement('css', 'messages messages--status');
    $assert_session->pageTextContains(t('This content is being edited by the user @name and is therefore locked to prevent other users changes.', [
      '@name' => $this->user2->getDisplayName(),
    ]));
    $assert_session->linkNotExists(t('Break lock'));
    $disabled_button = $assert_session->elementExists('css', 'input[disabled][data-drupal-selector="edit-submit"]');
    $this->assertTrue($disabled_button, t('The form cannot be submitted.'));

    // We unlock entity with user2.
    $this->drupalLogin($this->user2);
    // Edit a entity without saving.
    $this->drupalGet($this->entity->toUrl('edit-form'));
    $assert_session->waitForElement('css', 'messages messages--status');
    $assert_session->pageTextContains(t('This content is now locked by you against simultaneous editing.'));
    $page->pressButton(t('Save'));
    $assert_session->waitForElement('css', 'messages messages--status');
    $assert_session->pageTextContains(t('updated.'));
  }

}
