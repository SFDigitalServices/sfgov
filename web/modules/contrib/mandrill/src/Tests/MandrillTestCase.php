<?php

/**
 * @file
 * Contains \Drupal\mandrill\Tests\MandrillTestCase.
 */

namespace Drupal\mandrill\Tests;

use Drupal\mandrill\Plugin\Mail\MandrillTestMail;
use Drupal\simpletest\WebTestBase;

/**
 * Test core Mandrill functionality.
 *
 * @group mandrill
 */
class MandrillTestCase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['mandrill'];

  /**
   * Pre-test setup function.
   *
   * Enables dependencies.
   * Sets the mandrill_api_key variable to the test key.
   */
  protected function setUp() {
    parent::setUp();
    $config = \Drupal::service('config.factory')->getEditable('mandrill.settings');
    $config->set('mandrill_from_email', 'foo@bar.com');
    $config->set('mandrill_from_name', 'foo');
    $config->set('mandrill_api_key', 'MANDRILL_TEST_API_KEY');
    $config->save();
  }

  /**
   * Tests sending a message to multiple recipients.
   */
  public function testSendMessage() {
    $mailSystem = $this->getMandrillMail();
    $message = $this->getMessageTestData();
    $message['to'] = 'Recipient One <recipient.one@example.com>,' . 'Recipient Two <recipient.two@example.com>,' . 'Recipient Three <recipient.three@example.com>';
    $response = $mailSystem->mail($message);
    $this->assertTrue($response, 'Tested sending message to multiple recipients.');
  }

  /**
   * Tests sending a message to an invalid recipient.
   */
//  public function testSendMessageInvalidRecipient() {
//    $mailSystem = $this->getMandrillMail();
//    $message = $this->getMessageTestData();
//    $message['to'] = 'Recipient One <recipient.one>';
//    $response = $mailSystem->mail($message);
//    $this->assertFalse($response, 'Tested sending message to an invalid recipient.');
//  }

  /**
   * Tests sending a message to no recipients.
   */
//  public function testSendMessageNoRecipients() {
//    $mailSystem = $this->getMandrillMail();
//    $message = $this->getMessageTestData();
//    $message['to'] = '';
//    $response = $mailSystem->mail($message);
//    $this->assertFalse($response, 'Tested sending message to no recipients.');
//  }

  /**
   * Tests getting a list of subaccounts.
   */
  public function testGetSubAccounts() {
    $mandrillAPI = \Drupal::service('mandrill.test.api');
    $subAccounts = $mandrillAPI->getSubAccounts();
    $this->assertTrue(!empty($subAccounts), 'Tested retrieving sub-accounts.');
    if (!empty($subAccounts) && is_array($subAccounts)) {
      foreach ($subAccounts as $subAccount) {
        $this->assertTrue(!empty($subAccount['name']), 'Tested valid sub-account: ' . $subAccount['name']);
      }
    }
  }

  /**
   * Get the Mandrill Mail test plugin.
   *
   * @return \Drupal\mandrill\Plugin\Mail\MandrillTestMail
   */
  private function getMandrillMail() {
    return new MandrillTestMail();
  }

  /**
   * Gets message data used in tests.
   *
   * @return array
   */
  private function getMessageTestData() {
    return [
      'id' => 1,
      'module' => NULL,
      'body' => '<p>Mail content</p>',
      'subject' => 'Mail Subject',
      'from_email' => 'sender@example.com',
      'from_name' => 'Test Sender',
    ];
  }

}
