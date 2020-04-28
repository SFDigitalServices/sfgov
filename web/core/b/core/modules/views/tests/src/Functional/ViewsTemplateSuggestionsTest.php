<?php

namespace Drupal\Tests\views\Functional;

/**
 * Tests Views template suggestions.
 *
 * @group views
 */
class ViewsTemplateSuggestionsTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_page_display'];

  /**
   * Used by WebTestBase::setup()
   *
   * @var array
   *
   * @see \Drupal\simpletest\WebTestBase::setup()
   */
  public static $modules = ['views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp(TRUE);

    $this->enableViewsTestModule();
  }

  /**
   * Tests for views-view hook suggestions to be loaded.
   */
  public function testViewsViewSuggestions() {
    // Test with system theme using theme function.
    $this->drupalGet('test_page_display_200');

    // Assert that the default template is loaded.
    $this->assertSession()->elementExists('css', '.view.view-id-test_page_display');

    // Install theme to test with template system.
    \Drupal::service('theme_handler')->install(['views_test_suggestions_theme']);

    // Make the theme default then test for hook invocations.
    $this->config('system.theme')
      ->set('default', 'views_test_suggestions_theme')
      ->save();
    $this->assertEquals('views_test_suggestions_theme', $this->config('system.theme')->get('default'));

    $this->drupalGet('test_page_display_200');

    // Assert template views-view--test-page-display.html.twig is not loaded
    // due having less specificity.
    $this->assertSession()->pageTextNotContains('**THIS SHOULD NOT BE LOADED**');

    // Assert that we are using the correct template
    // views-view--test-page-display--page-3.html.twig.
    $this->assertSession()->pageTextContains('This has been done during SprintWeekend2018 London');

    // Assert the base template views-view.html.twig is not loaded either.
    $this->assertSession()->elementNotExists('css', '.view.view-id-test_page_display');
  }

}
