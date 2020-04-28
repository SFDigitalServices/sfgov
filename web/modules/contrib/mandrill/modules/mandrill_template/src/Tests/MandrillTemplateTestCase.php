<?php

/**
 * @file
 * Test class and methods for the Mandrill Template module.
 */

namespace Drupal\mandrill_template\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test Mandrill Template functionality.
 *
 * @group mandrill
 */
class MandrillTemplateTestCase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['mandrill', 'mandrill_template'];

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
   * Tests getting a list of templates for a given label.
   */
  public function testGetTemplates() {
    /* @var $mandrillAPI \Drupal\mandrill\MandrillTestAPI */
    $mandrillAPI = \Drupal::service('mandrill.test.api');

    $templates = $mandrillAPI->getTemplates();
    $this->assertTrue(!empty($templates), 'Tested retrieving templates.');

    if (!empty($templates) && is_array($templates)) {
      foreach ($templates as $template) {
        $this->assertTrue(!empty($template['name']), 'Tested valid template: ' . $template['name']);
      }
    }
  }

  /**
   * Test sending a templated message to multiple recipients.
   */
  public function testSendTemplatedMessage() {
    $to = 'Recipient One <recipient.one@example.com>,'
      . 'Recipient Two <recipient.two@example.com>,'
      . 'Recipient Three <recipient.three@example.com>';

    $message = $this->getMessageTestData();
    $message['to'] = $to;

    $template_id = 'Test Template';
    $template_content = array(
      'name' => 'Recipient',
    );

    /* @var $mandrillAPI \Drupal\mandrill\MandrillTestAPI */
    $mandrillAPI = \Drupal::service('mandrill.test.api');

    $response = $mandrillAPI->sendTemplate($message, $template_id, $template_content);

    $this->assertNotNull($response, 'Tested response from sending templated message.');

    if (isset($response['status'])) {
      $this->assertNotEqual($response['status'], 'error', 'Tested response status: ' . $response['status'] . ', ' . $response['message']);
    }
  }

  /**
   * Test sending a templated message using an invalid template.
   */
  public function testSendTemplatedMessageInvalidTemplate() {
    $to = 'Recipient One <recipient.one@example.com>';

    $message = $this->getMessageTestData();
    $message['to'] = $to;

    $template_id = 'Invalid Template';
    $template_content = array(
      'name' => 'Recipient',
    );

    /* @var $mandrillAPI \Drupal\mandrill\MandrillTestAPI */
    $mandrillAPI = \Drupal::service('mandrill.test.api');

    $response = $mandrillAPI->sendTemplate($message, $template_id, $template_content);

    $this->assertNotNull($response, 'Tested response from sending invalid templated message.');

    if (isset($response['status'])) {
      $this->assertEqual($response['status'], 'error', 'Tested response status: ' . $response['status'] . ', ' . $response['message']);
    }
  }

  /**
   * Gets message data used in tests.
   */
  protected function getMessageTestData() {
    $message = array(
      'id' => 1,
      'body' => '<p>Mail content</p>',
      'subject' => 'Mail Subject',
      'from_email' => 'sender@example.com',
      'from_name' => 'Test Sender',
    );

    return $message;
  }

}
