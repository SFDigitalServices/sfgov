<?php

/**
 * @file
 * Contains \Drupal\Tests\toc_api\Unit\TocFormatterTest.
 */

namespace Drupal\Tests\toc_api\Unit;

use Drupal\toc_api\TocFormatter;
use Drupal\Tests\UnitTestCase;

/**
 * Tests TOC API formatter.
 *
 * @group TocApi
 *
 * @coversDefaultClass \Drupal\toc_api\TocFormatter
 */
class TocFormatterTest extends UnitTestCase {

  /**
   * The table of contents formatter.
   *
   * @var \Drupal\toc_api\TocFormatter
   */
  protected $formatter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->formatter = new TocFormatter();
  }

  /**
   * Tests converting string to valid HTML id with TocFormatter::convertStringToId().
   *
   * @param string $string
   *   The string to run through $this->formatter->convertStringToId().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see TocFormatter::convertStringToId()
   *
   * @dataProvider providerConvertStringToId
   */
  public function testConvertStringToId($string, $expected) {
    $result = $this->formatter->convertStringToId($string);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testConvertStringToId().
   *
   * @see testConvertStringToId()
   */
  public function providerConvertStringToId() {
    $tests[] = ['One', 'one'];
    $tests[] = ['One   two', 'one-two'];
    $tests[] = ['One ! two', 'one-two'];
    $tests[] = ['--One ! two--', 'one-two'];
    $tests[] = ['Spécial characters', 'special-characters'];
    return $tests;
  }

  /**
   * Tests converting number to list style type with TocFormatter::convertNumberToListTypeValue().
   *
   * @param int $number
   *   The number to run through TocFormatter::convertNumberToListTypeValue().
   * @param string $type
   *   The type to run through $this->formatter->convertNumberToListTypeValue().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see TocFormatter::convertNumberToListTypeValue()
   *
   * @dataProvider providerConvertNumberToListTypeValue
   */
  public function testConvertNumberToListTypeValue($number, $type, $expected) {
    $result = $this->formatter->convertNumberToListTypeValue($number, $type);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testConvertNumberToListTypeValue().
   *
   * @see testConvertNumberToListTypeValue()
   */
  public function providerConvertNumberToListTypeValue() {
    $tests[] = [1, NULL, 1];
    $tests[] = [1, 'decimal', '1'];
    $tests[] = [1, 'random', '1'];
    $tests[] = [0, 'random', '0'];

    $tests[] = [1, 'lower-alpha', 'a'];
    $tests[] = [1, 'upper-alpha', 'A'];
    $tests[] = [25, 'lower-alpha', 'y'];
    $tests[] = [26, 'lower-alpha', 'z'];
    $tests[] = [27, 'lower-alpha', 'a'];
    $tests[] = [52, 'lower-alpha', 'z'];
    $tests[] = [53, 'lower-alpha', 'a'];
    $tests[] = [0, 'lower-alpha', '0'];

    $tests[] = [1, 'lower-roman', 'i'];
    $tests[] = [1, 'upper-roman', 'I'];
    $tests[] = [0, 'lower-roman', '0'];
    $tests[] = [4, 'lower-roman', 'iv'];
    return $tests;
  }

  /**
   * Tests converting header keys to list style type values with TocFormatter::convertHeaderKeysToValues().
   *
   * @param array $keys
   *   The array to run through TocFormatter::convertHeaderKeysToValues().
   * @param array $options
   *   The array to run through TocFormatter::convertHeaderKeysToValues().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see TocFormatter::convertHeaderKeysToValues()
   *
   * @dataProvider providerConvertHeaderKeysToValues
   */
  public function testConvertHeaderKeysToValues(array $keys, array $options, $expected) {
    $result = $this->formatter->convertHeaderKeysToValues($keys, $options);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testConvertHeaderKeysToValues().
   *
   * @see testConvertHeaderKeysToValues()
   */
  public function providerConvertHeaderKeysToValues() {
    $options = [
      'number_path_truncate' => TRUE,
      'headers' => [
        'h1' => ['number_type' => 'decimal'],
        'h2' => ['number_type' => 'lower-alpha'],
        'h3' => ['number_type' => 'lower-roman'],
      ],
    ];
    $tests[] = [['h1' => 2, 'h2' => 2, 'h3' => 2], $options, ['h1' => '2', 'h2' => 'b', 'h3' => 'ii']];
    $tests[] = [['h1' => 0, 'h2' => 2, 'h3' => 2], $options, ['h2' => 'b', 'h3' => 'ii']];
    $tests[] = [['h1' => 2, 'h2' => 2, 'h3' => 0], $options, ['h1' => '2', 'h2' => 'b']];
    $tests[] = [['h1' => 2, 'h2' => 2, 'h3' => 0, 'h4' => 0], $options, ['h1' => '2', 'h2' => 'b']];
    $tests[] = [['h1' => 2, 'h2' => 0, 'h3' => 2], $options, ['h1' => '2', 'h2' => '0', 'h3' => 'ii']];
    $tests[] = [['h1' => 2, 'h2' => 2, 'h3' => 0], ['number_path_truncate' => FALSE] + $options, ['h1' => '2', 'h2' => 'b', 'h3' => '0']];
    return $tests;
  }

}
