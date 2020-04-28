<?php

namespace Drupal\Tests\telephone_formatter\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use libphonenumber\PhoneNumberFormat;

/**
 * Tests the creation of telephone fields.
 *
 * @group telephone
 */
class FieldFormatterTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'node',
    'telephone',
    'telephone_formatter',
  ];

  /**
   * A user with permission to create articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);
    $this->webUser = $this->drupalCreateUser(['create page content', 'edit own page content']);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Test function for testTelephoneField().
   *
   * @dataProvider telephoneDataProvider
   *   Test different scenarios.
   */
  public function testTelephoneFieldFallback($format, $link, $default_country, $expected, $value) {
    $this->generateTelephoneField([
      'format' => $format,
      'link' => $link,
      'default_country' => $default_country,
    ]);
    $node = $this->drupalCreateNode(['field_telephone' => [$value]]);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->responseContains($expected);
  }

  /**
   * Helper method for telephone field generation.
   */
  protected function generateTelephoneField($settings = []) {
    // Add the telephone field to the article content type.
    FieldStorageConfig::create([
      'field_name' => 'field_telephone',
      'entity_type' => 'node',
      'type' => 'telephone',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_telephone',
      'label' => 'Telephone Number',
      'entity_type' => 'node',
      'bundle' => 'page',
    ])->save();

    entity_get_display('node', 'page', 'default')
      ->setComponent('field_telephone', [
        'type' => 'telephone_formatter',
        'weight' => 1,
        'settings' => [
          'format' => $settings['format'],
          'link' => $settings['link'],
          'default_country' => $settings['default_country'],
        ],
      ])
      ->save();
  }

  /**
   * Different test scenarios for Telephone formatter.
   */
  public function telephoneDataProvider() {
    return [
      [
        'format' => PhoneNumberFormat::INTERNATIONAL,
        'link' => FALSE,
        'default_country' => NULL,
        'expected' => '98765432',
        'value' => '98765432',
      ],
      [
        'format' => PhoneNumberFormat::INTERNATIONAL,
        'link' => TRUE,
        'default_country' => 'SI',
        'expected' => '<a href="tel:+386-1-425-68-58">+386 1 425 68 58</a>',
        'value' => '014256858',
      ],
      [
        'format' => PhoneNumberFormat::E164,
        'link' => TRUE,
        'default_country' => 'SI',
        'expected' => '<a href="tel:+386-51-333-333">+38651333333</a>',
        'value' => '051333333',
      ],
      [
        'format' => PhoneNumberFormat::NATIONAL,
        'link' => FALSE,
        'default_country' => 'SI',
        'expected' => '(03) 425 68 58',
        'value' => '034256858',
      ],
      [
        'format' => PhoneNumberFormat::RFC3966,
        'link' => FALSE,
        'default_country' => 'SI',
        'expected' => 'tel:+386-70-123-456',
        'value' => '070 123 456',
      ],
    ];
  }

}
