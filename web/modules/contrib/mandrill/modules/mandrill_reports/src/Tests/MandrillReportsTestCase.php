<?php

/**
 * @file
 * Test class and methods for the Mandrill Reports module.
 */

namespace Drupal\mandrill_reports\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test Mandrill Reports functionality.
 *
 * @group mandrill
 */
class MandrillReportsTestCase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['mandrill', 'mandrill_reports'];

  /**
   * Pre-test setup function.
   *
   * Enables dependencies.
   * Sets the mandrill_api_key variable to the test key.
   */
  protected function setUp() {
    parent::setUp();
    $config = \Drupal::service('config.factory')->getEditable('mandrill.settings');
    $config->set('mandrill_api_key', MANDRILL_TEST_API_KEY);
  }

  /**
   * Tests getting Mandrill reports data.
   */
  public function testGetReportsData() {
    /* @var $reports \Drupal\mandrill_reports\MandrillReportsService */
    $reports = \Drupal::service('mandrill_reports.test.service');

    $reports_data = array(
      'user' => $reports->getUser(),
      'tags' => $reports->getTags(),
      'all_time_series' => $reports->getTagsAllTimeSeries(),
      'senders' => $reports->getSenders(),
      'urls' => $reports->getUrls(),
    );

    $this->assertTrue(!empty($reports_data['user']), 'Tested user report data exists.');
    $this->assertTrue(!empty($reports_data['tags']), 'Tested tags report data exists.');
    $this->assertTrue(!empty($reports_data['all_time_series']), 'Tested all time series report data exists.');
    $this->assertTrue(!empty($reports_data['senders']), 'Tested senders report data exists.');
    $this->assertTrue(!empty($reports_data['urls']), 'Tested URLs report data exists.');
  }

}
