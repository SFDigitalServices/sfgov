<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

/**
 * Testing the basic functionality of External Links.
 *
 * @group Extlink
 */
class ExtlinkTestTarget extends ExtlinkTestBase {

  /**
   * Checks to see if extlink adds target and rel attributes.
   */
  public function testExtlinkTarget() {
    // Target Enabled.
    $this->config('extlink.settings')->set('extlink_target', TRUE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com">Google!</a></p>',
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

    // Test that the page has the external link span.
    $externalLink = $page->find('css', 'span.ext');
    $this->assertTrue($externalLink->isVisible(), 'External Link Exists.');
    $link = $page->findLink('Google!');

    // Link should have target attribute.
    $this->assertTrue($link->getAttribute('target') === '_blank', 'ExtLink target attribute is not "_blank".');

    // Link should have rel attribute 'noopener noreferrer'
    $this->assertTrue($link->getAttribute('rel') === 'noopener noreferrer' || $link->getAttribute('rel') === 'noreferrer noopener', 'ExtLink rel attribute is not "noopener noreferrer".');
  }

  /**
   * Checks to see if extlink changes the target attribute
   */
  public function testExtlinkTargetNoOverride() {
    // Target Enabled.
    $this->config('extlink.settings')->set('extlink_target', TRUE)->save();
    $this->config('extlink.settings')->set('extlink_target_no_override', TRUE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com" target="_self">Google!</a></p>',
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

    // Test that the page has the external link span.
    $externalLink = $page->find('css', 'span.ext');
    $this->assertTrue($externalLink->isVisible(), 'External Link Exists.');
    $link = $page->findLink('Google!');

    // Link should have target attribute.
    $this->assertTrue($link->getAttribute('target') === '_self', 'ExtLink target attribute is not "_self".');

    // Link should have rel attribute 'noopener noreferrer'
    $this->assertTrue($link->getAttribute('rel') === 'noopener noreferrer' || $link->getAttribute('rel') === 'noreferrer noopener', 'ExtLink rel attribute is not "noopener noreferrer".');
  }

}
