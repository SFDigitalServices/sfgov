<?php

namespace Drupal\Tests\viewfield\FunctionalJavascript;

/**
 * Tests Viewfield widgets.
 *
 * @group viewfield
 */
class ViewfieldWidgetTest extends ViewfieldFunctionalTestBase {

  /**
   * Test select widget.
   */
  public function testSelectWidget() {
    $this->form->setComponent('field_view_test', [
      'type' => 'viewfield_select',
    ])->save();

    $this->display->setComponent('field_view_test', [
      'type' => 'viewfield_title',
      'weight' => 1,
    ])->save();

    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Confirm field label and description are rendered.
    $this->drupalGet('node/add/article_test');

    $session->fieldExists("field_view_test[0][target_id]");
    $session->fieldExists("field_view_test[0][display_id]");
    $session->fieldExists("field_view_test[0][arguments]");
    $session->responseContains('Viewfield');
    $session->responseContains('Viewfield description');

    $viewfield_target = $session->fieldExists('field_view_test[0][target_id]');

    // Test basic entry of color field.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];

    $viewfield_target->setValue('content_test');
    $session->assertWaitOnAjaxRequest();

    $viewfield_display = $session->fieldExists('field_view_test[0][display_id]');
    $viewfield_display->setValue('block_1');

    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Test response.
    $session->responseContains('content_test');
    $session->responseContains('block_1');
    $session->responseContains('article_test');
  }

}