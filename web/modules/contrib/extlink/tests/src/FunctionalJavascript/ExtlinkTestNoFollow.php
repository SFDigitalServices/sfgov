<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

/**
 * Testing the rel nofollow/follow functionality of External Links.
 *
 * @group Extlink
 */
class ExtlinkTestNoFollow extends ExtlinkTestBase {

  /**
   * Checks to see if extlink and nofollow work together when both are enabled.
   */
  public function testExtlinkEnabledNoFollowEnabled() {
    // No Follow Enabled.
    $this->config('extlink.settings')->set('extlink_nofollow', TRUE)->save();

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
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/ExtlinkEnabledNoFollowEnabled.png');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link span.
    $externalLink = $page->find('css', 'span.ext');
    $this->assertTrue($externalLink->isVisible(), 'External Link does not exist.');

    // Does the anchor tag have no follow?
    $link = $page->findLink('Google!');
    $this->assertTrue($link->hasAttribute('rel'), 'ExtLink does not have rel attribute.');
    $this->assertTrue($link->getAttribute('rel') === 'nofollow', 'ExtLink rel attribute is not "nofollow".');
  }

  /**
   * Checks to see if external link no follow works with extlink disabled.
   */
  public function testExtlinkDisabledNoFollowEnabled() {
    // No Follow Enabled, Disable Extlink.
    $this->config('extlink.settings')->set('extlink_nofollow', TRUE)->save();
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
          'value' => '<p><a href="http://google.com">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/ExtlinkDisabledNoFollowEnabled.png');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link span.
    $externalLink = $page->find('css', 'span.ext');
    $this->assertTrue(is_null($externalLink), 'External Link exists.');

    // Does the anchor tag have no follow?
    $link = $page->findLink('Google!');
    $this->assertTrue($link->hasAttribute('rel'), 'ExtLink does not have rel attribute.');
    $this->assertTrue($link->getAttribute('rel') === 'nofollow', 'ExtLink rel attribute is not "nofollow".');
  }

  /**
   * Checks to see if rel no follow is added if disabled.
   */
  public function testExtlinkDisabledNoFollowDisabled() {
    // No Follow Enabled, Extlink Disabled.
    $this->config('extlink.settings')->set('extlink_nofollow', FALSE)->save();
    $this->config('extlink.settings')->set('extlink_follow_no_override', FALSE)->save();
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
          'value' => '<p><a href="http://google.com">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/ExtlinkDisabledNoFollowDisabled.png');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page doesn't have the external link span.
    $externalLink = $page->find('css', 'span.ext');
    $this->assertTrue(is_null($externalLink), 'External Link exists.');

    // Check for no 'nofollow'.
    $link = $page->findLink('Google!');
    $this->assertFalse($link->hasAttribute('rel'), 'ExtLink has rel attribute.');
  }

  /**
   * Checks to see if rel no follow is overridden.
   */
  public function testExtlinkNoFollowNoOverride() {
    // No Follow Enabled, Extlink Disabled.
    $this->config('extlink.settings')->set('extlink_nofollow', TRUE)->save();
    $this->config('extlink.settings')->set('extlink_follow_no_override', TRUE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="http://google.com" rel="follow">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/ExtlinkNoFollowNoOverride.png');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link span.
    $externalLink = $page->find('css', 'span.ext');
    $this->assertTrue($externalLink->isVisible(), 'External Link Exists.');

    // Does the anchor tag have no follow?
    $link = $page->findLink('Google!');
    $this->assertTrue($link->hasAttribute('rel'), 'ExtLink does not have rel attribute.');
    $this->assertTrue($link->getAttribute('rel') === 'follow', 'rel attribute is not "follow".');
  }

}
