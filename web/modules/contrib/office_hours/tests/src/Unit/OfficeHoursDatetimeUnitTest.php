<?php

namespace Drupal\Tests\office_hours\Unit;

use Drupal\office_hours\Element\OfficeHoursDatetime;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the new entity API for the office_hours field type.
 *
 * @see https://www.drupal.org/docs/automated-testing/phpunit-in-drupal
 * @see https://www.drupal.org/docs/testing/phpunit-in-drupal/running-phpunit-tests-within-phpstorm
 *
 *
 * @group office_hours
 */
class OfficeHoursDatetimeUnitTest extends UnitTestCase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['office_hours'];

  /**
   * Tests using entity fields of the datetime field type.
   */
  public function testDateTimeIsEmpty() {

    $this::assertTrue(OfficeHoursDatetime::isEmpty(NULL), 'Test Datetime NULL is empty.');
    $this::assertNotTrue(OfficeHoursDatetime::isEmpty(-1), 'Test 24:00 is not empty.');
    $this::assertTrue(OfficeHoursDatetime::isEmpty(''), 'Test empty slot is empty.');
    $this::assertTrue(OfficeHoursDatetime::isEmpty([
      'time' => '',
    ]), "Test empty 'time' value is empty.");
    $this::assertNotTrue(OfficeHoursDatetime::isEmpty([
      'time' => 'a time',
    ]), "Test not-empty 'time' value is not empty.");
    $this::assertTrue(OfficeHoursDatetime::isEmpty([
      'day' => '2',
      'starthours' => '',
      'endhours' => '',
      'comment' => '',
    ]), "Test complete array - only 'day' is set.");
    $this::assertNotTrue(OfficeHoursDatetime::isEmpty([
      'day' => '2',
      'starthours' => '',
      'endhours' => '',
      'comment' => 'There is a comment, so not empty.',
    ]), "Test complete array - only 'day' and 'comment' is set.");
    $this::assertTrue(OfficeHoursDatetime::isEmpty([
      'day' => NULL,
      'starthours' => NULL,
      'endhours' => NULL,
      'comment' => NULL,
    ]), "Test complete array with 4 NULL (from devel_generate).");
  }

}
