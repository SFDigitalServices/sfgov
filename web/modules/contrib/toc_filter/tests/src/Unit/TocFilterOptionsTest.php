<?php

/**
 * @file
 * Contains \Drupal\Tests\toc_filter\Unit\TocFilterOptionsTest.
 */

namespace Drupal\Tests\toc_filter\Unit;

use Drupal\toc_filter\Plugin\Filter\TocFilter;
use Drupal\Tests\UnitTestCase;

/**
 * Tests TOC filter formatter.
 *
 * @group TocFilter
 *
 * @coversDefaultClass \Drupal\toc_filter\Plugin\Filter\TocFilter
 */
class TocFilterOptionsTest extends UnitTestCase {

  /**
   * Tests converting string of options with TocFilter::parseOptions().
   *
   * @param string $string
   *   The string to run through TocFilter::parseOptions().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see TocFilter::parseOptions()
   *
   * @dataProvider providerParseOptions
   */
  public function testParseOptions($string, $expected) {
    $result = TocFilter::parseOptions($string);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testParseOptions().
   *
   * @see testParseOptions()
   */
  public function providerParseOptions() {
    $tests[] = ['value name=\'value\'', ['value' => TRUE, 'name' => 'value']];
    $tests[] = ['one=\'value\' two=value three="value" &nbsp; four=&quot;value&quot;', ['one' => 'value', 'two' => 'value', 'three' => 'value', 'four' => 'value']];
    $tests[] = ['one=\'value\' two=value three="value" &nbsp; four=&quot;value&quot;', ['one' => 'value', 'two' => 'value', 'three' => 'value', 'four' => 'value']];
    $tests[] = ['one=1 two=2.0 three="3.3"', ['one' => 1, 'two' => 2, 'three' => 3.3]];
    $tests[] = ['one=TRUE two=False three', ['one' => TRUE, 'two' => FALSE, 'three' => TRUE]];
    $tests[] = ['parent_option.child_option=value', ['parent_option' => ['child_option' => 'value']]];
    $tests[] = ['h2.number_type=decimal', ['headers' => ['h2' => ['number_type' => 'decimal']]]];
    return $tests;
  }

}
