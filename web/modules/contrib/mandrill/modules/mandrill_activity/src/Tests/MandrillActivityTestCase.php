<?php

/**
 * @file
 * Test class and methods for the Mandrill Activity module.
 */

namespace Drupal\mandrill_activity\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test Mandrill Activity functionality.
 *
 * @group mandrill
 */
class MandrillActivityTestCase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['mandrill', 'mandrill_activity'];

  /**
   * Pre-test setup function.
   *
   * Enables dependencies.
   * Sets the mandrill_api_key variable to the test key.
   */
  protected function setUp() {
    parent::setUp();
    $config = \Drupal::service('config.factory')->getEditable('mandrill.settings');
    $config->set('mandrill_api_key', 'MANDRILL_TEST_API_KEY');
  }

  /**
   * Tests getting an array of message activity for a given email address.
   */
  public function testGetActivity() {
    $email = 'recipient@example.com';

    /* @var $mandrillAPI \Drupal\mandrill\MandrillTestAPI */
    $mandrillAPI = \Drupal::service('mandrill.test.api');

    $activity = $mandrillAPI->getMessages($email);

    $this->assertTrue(!empty($activity), 'Tested retrieving activity.');

    if (!empty($activity) && is_array($activity)) {
      foreach ($activity as $message) {
        $this->assertEqual($message['email'], $email, 'Tested valid message: ' . $message['subject']);
      }
    }
  }

}
