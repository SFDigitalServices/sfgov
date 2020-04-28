<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

/**
 * Testing the basic functionality of External Links.
 *
 * @group Extlink
 */
class ExtlinkTest extends ExtlinkTestBase {

  /**
   * Checks to see if external link gets extlink span.
   */
  public function testExtlink() {
    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com">Google!</a></p><p><a href="mailto:someone@example.com">Send Mail</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/Extlink.png');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Send Mail'));

    // Test that the page has the external link span.
    $externalLink = $page->find('css', 'span.ext');
    $this->assertTrue($externalLink->isVisible(), 'External Link Exists.');

    // Test that the page has the Mailto external link span.
    $mailToLink = $page->find('css', 'span.mailto');
    $this->assertTrue($mailToLink->isVisible(), 'External Link MailTo Exists.');
  }

  /**
   * Checks to see if external link works correctly when disabled.
   */
  public function testExtlinkDisabled() {
    // Disable Extlink.
    $this->config('extlink.settings')->set('extlink_class', '0')->save();
    $this->config('extlink.settings')->set('extlink_mailto_class', '0')->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com">Google!</a></p><p><a href="mailto:someone@example.com">Send Mail</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/ExtlinkDisabled.png');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Send Mail'));

    // Test that the page has the external link span.
    $externalLink = $page->find('css', 'span.ext');
    $this->assertTrue(is_null($externalLink), 'External Link does not exist.');

    // Test that the page has the Mailto external link span.
    $mailToLink = $page->find('css', 'span.mailto');
    $this->assertTrue(is_null($mailToLink), 'External Link MailTo does not exist.');
  }

  /**
   * Checks to see if external link works with an extended set of links.
   */
  public function testExtlinkDomainMatching() {
    // Login.
    $this->drupalLogin($this->adminUser);

    $domains = [
      'http://www.example.com',
      'http://www.example.com:8080',
      'http://www.example.co.uk',
      'http://test.example.com',
      'http://example.com',
      'http://www.whatever.com',
      'http://www.domain.org',
      'http://www.domain.nl',
      'http://www.domain.de',
      'http://www.auspigs.com',
      'http://www.usapigs.com',
      'http://user:password@example.com',
    ];

    // Build the html for the page.
    $node_html = '';
    foreach ($domains as $x => $x_value) {
      $node_html .= '<p><a href="' . $x_value . '">' . $x_value . '</a></p><p>';
    }

    // Create the node.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => $node_html,
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/' . __FUNCTION__ . '.png');
    $this->assertSession()->statusCodeEquals(200);

    // Test that the page has an external link on each link.
    foreach ($domains as $x => $x_value) {
      $externalLink = $page->findLink($x_value);
      $this->assertTrue($externalLink->hasClass($this->config('extlink.settings')->get('extlink_class')), 'External Link failed for "' . $x_value . '"');
    }

  }

  /**
   * Checks to see if external link works with an extended set of links.
   */
  public function testExtlinkDomainMatchingExcludeSubDomainsEnabled() {
    $this->config('extlink.settings')->set('extlink_subdomains', TRUE)->save();
    $this->testExtlinkDomainMatching();
  }

}
