<?php

/**
 * @file
 * Contains \Drupal\Tests\toc_api\Unit\TocTest.
 */

namespace Drupal\Tests\toc_api\Unit;

use Drupal\Component\Utility\Variable;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\toc_api\Toc;
use Drupal\toc_api\TocFormatter;

/**
 * Tests TOC API formatter.
 *
 * @group TocApi
 *
 * @coversDefaultClass \Drupal\toc_api\Toc
 */
class TocTest extends UnitTestCase {

  /**
   * The service container used for testing.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $this->container->set('toc_api.formatter', new TocFormatter());
    \Drupal::setContainer($this->container);
  }

  /**
   * Tests parsing headers and creating a table of contents index.
   *
   * @see Toc::getIndex()
   */
  public function testIndex() {
    // Check default index. This covers type, tag, level, indent, keys, parent,
    // child, id, and title.
    $toc = new Toc('<h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', []);
    $this->assertArraySubset([
      '1.0.0' => [
        'type' => 'decimal',
        'tag' => 'h2',
        'level' => 2,
        'key' => '1.0.0',
        'keys' => [
          'h2' => 1,
          'h3' => 0,
          'h4' => 0,
        ],
        'indent' => 0,
        'path' => '1',
        'number' => 1,
        'value' => '1',
        'parent' => NULL,
        'children' => [
          '1.1.0' => '1.1.0',
        ],
        'id' => 'header-2',
        'title' => 'header 2',
      ],
      '1.1.0' => [
        'type' => 'decimal',
        'tag' => 'h3',
        'level' => 3,
        'key' => '1.1.0',
        'keys' => [
          'h2' => 1,
          'h3' => 1,
          'h4' => 0,
        ],
        'indent' => 1,
        'path' => '1.1',
        'number' => 1,
        'value' => '1',
        'parent' => '1.0.0',
        'children' => [
          '1.1.1' => '1.1.1',
          '1.1.2' => '1.1.2',
        ],
        'id' => 'header-3',
        'title' => 'header 3',
      ],
      '1.1.1' => [
        'type' => 'decimal',
        'tag' => 'h4',
        'level' => 4,
        'key' => '1.1.1',
        'keys' => [
          'h2' => 1,
          'h3' => 1,
          'h4' => 1,
        ],
        'indent' => 2,
        'path' => '1.1.1',
        'number' => 1,
        'value' => '1',
        'parent' => '1.1.0',
        'children' => [],
        'id' => 'header-4',
        'title' => 'header 4',
      ],
      '1.1.2' => [
        'type' => 'decimal',
        'tag' => 'h4',
        'level' => 4,
        'key' => '1.1.2',
        'keys' => [
          'h2' => 1,
          'h3' => 1,
          'h4' => 2,
        ],
        'indent' => 2,
        'path' => '1.1.2',
        'number' => 2,
        'value' => '2',
        'parent' => '1.1.0',
        'children' => [],
        'id' => 'header-4-01',
        'title' => 'header 4',
      ],
      '2.0.0' => [
        'type' => 'decimal',
        'tag' => 'h2',
        'level' => 2,
        'key' => '2.0.0',
        'keys' => [
          'h2' => 2,
          'h3' => 0,
          'h4' => 0,
        ],
        'indent' => 0,
        'path' => '2',
        'number' => 2,
        'value' => '2',
        'parent' => NULL,
        'children' => [],
        'id' => 'header-2-01',
        'title' => 'header 2',
      ],
    ], $toc->getIndex());

    // Check custom options. This covers type, value, and path.
    $options = [
      'headers' => [
        'h2' => ['number_type' => 'decimal'],
        'h3' => ['number_type' => 'lower-alpha'],
        'h4' => ['number_type' => 'lower-roman'],
      ],
    ];
    $toc = new Toc('<h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', $options);
    $this->assertArraySubset([
      '1.0.0' => [
        'type' => 'decimal',
        'path' => '1',
        'value' => '1',
      ],
      '1.1.0' => [
        'type' => 'lower-alpha',
        'path' => '1.a',
        'value' => 'a',
      ],
      '1.1.1' => [
        'type' => 'lower-roman',
        'path' => '1.a.i',
        'value' => 'i',
      ],
      '1.1.2' => [
        'type' => 'lower-roman',
        'path' => '1.a.ii',
        'value' => 'ii',
      ],
      '2.0.0' => [
        'type' => 'decimal',
        'path' => '2',
        'value' => '2',
      ],
    ], $toc->getIndex());

    // Check paths without truncation.
    $options = [
      'number_path_truncate' => FALSE,
      'headers' => [
        'h2' => ['number_type' => 'decimal'],
        'h3' => ['number_type' => 'lower-alpha'],
        'h4' => ['number_type' => 'lower-roman'],
      ],
    ];
    $toc = new Toc('<h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', $options);
    $this->assertArraySubset([
      '1.0.0' => [
        'path' => '1.0.0',
      ],
      '1.1.0' => [
        'path' => '1.a.0',
      ],
      '1.1.1' => [
        'path' => '1.a.i',
      ],
      '1.1.2' => [
        'path' => '1.a.ii',
      ],
      '2.0.0' => [
        'path' => '2.0.0',
      ],
    ], $toc->getIndex());

    // Check ids by keys.
    $options = [
      'number_path_truncate' => FALSE,
      'header_id' => 'key',
      'headers' => [
        'h2' => ['number_type' => 'decimal'],
        'h3' => ['number_type' => 'lower-alpha'],
        'h4' => ['number_type' => 'lower-roman'],
      ],
    ];
    $toc = new Toc('<h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', $options);
    $this->assertArraySubset([
      '1.0.0' => [
        'path' => '1.0.0',
        'id' => 'section-1.0.0',
      ],
      '1.1.0' => [
        'path' => '1.a.0',
        'id' => 'section-1.1.0',
      ],
      '1.1.1' => [
        'path' => '1.a.i',
        'id' => 'section-1.1.1',
      ],
      '1.1.2' => [
        'path' => '1.a.ii',
        'id' => 'section-1.1.2',
      ],
      '2.0.0' => [
        'path' => '2.0.0',
        'id' => 'section-2.0.0',
      ],
    ], $toc->getIndex());

    // Check ids by path with prefix.
    $options = [
      'number_path_truncate' => FALSE,
      'header_id' => 'number_path',
      'header_id_prefix' => 'header',
      'headers' => [
        'h2' => ['number_type' => 'decimal'],
        'h3' => ['number_type' => 'lower-alpha'],
        'h4' => ['number_type' => 'lower-roman'],
      ],
    ];
    $toc = new Toc('<h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', $options);
    $this->assertArraySubset([
      '1.0.0' => [
        'path' => '1.0.0',
        'id' => 'header-1.0.0',
      ],
      '1.1.0' => [
        'id' => 'header-1.a.0',
      ],
      '1.1.1' => [
        'id' => 'header-1.a.i',
      ],
      '1.1.2' => [
        'id' => 'header-1.a.ii',
      ],
      '2.0.0' => [
        'path' => '2.0.0',
        'id' => 'header-2.0.0',
      ],
    ], $toc->getIndex());

    // Check existing ids.
    $toc = new Toc('<h2>header 2</h2><h3 id="three">header 3</h3><h4 id="four">header 4</h4><h4 id="four">header 4</h4><h2>header 2</h2>', []);
    $this->assertArraySubset([
      '1.0.0' => [
        'id' => 'header-2',
      ],
      '1.1.0' => [
        'id' => 'three',
      ],
      '1.1.1' => [
        'id' => 'four',
      ],
      '1.1.2' => [
        'id' => 'four-01',
      ],
      '2.0.0' => [
        'id' => 'header-2-01',
      ],
    ], $toc->getIndex());

    // Check missing parent.
    $toc = new Toc('<h2>header 2</h2><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', []);
    $this->assertArraySubset([
      '1.0.0' => [
        'parent' => NULL,
        'children' => [
          '1.0.1' => '1.0.1',
          '1.0.2' => '1.0.2',
        ],
      ],
      '1.0.1' => [
        'parent' => '1.0.0',
        'children' => [],
      ],
      '1.0.2' => [
        'parent' => '1.0.0',
        'children' => [],
      ],
      '2.0.0' => [
        'parent' => NULL,
        'children' => [],
      ],
    ], $toc->getIndex());

    // $this->dumpArraySubset($toc->getIndex(), '$toc->getIndex()');
  }

  /**
   * Tests converting table of contents index to hierarchical tree.
   *
   * @see Toc::getTree()
   */
  public function testTree() {
    // Check parent child relationship.
    $toc = new Toc('<h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', []);
    $this->assertArraySubset([
      'title' => 'Table of Contents',
      'below_type' => 'decimal',
      'below' => [
        '1.0.0' => [
          'type' => 'decimal',
          'below_type' => 'decimal',
          'below' => [
            '1.1.0' => [
              'below_type' => 'decimal',
              'below' => [
                '1.1.1' => [
                  'below_type' => '',
                  'below' => [],
                ],
                '1.1.2' => [
                  'below_type' => '',
                  'below' => [],
                ],
              ],
            ],
          ],
        ],
        '2.0.0' => [
          'type' => 'decimal',
          'below_type' => '',
          'below' => [],
        ],
      ],
    ], $toc->getTree());

    // Checkout below type
    // Check paths without truncation.
    $options = [
      'number_path_truncate' => FALSE,
      'headers' => [
        'h2' => ['number_type' => 'decimal'],
        'h3' => ['number_type' => 'lower-alpha'],
        'h4' => ['number_type' => 'lower-roman'],
      ],
    ];
    $toc = new Toc('<h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', $options);
    $this->assertArraySubset([
      'title' => 'Table of Contents',
      'below_type' => 'decimal',
      'below' => [
        '1.0.0' => [
          'type' => 'decimal',
          'below_type' => 'lower-alpha',
          'below' => [
            '1.1.0' => [
              'below_type' => 'lower-roman',
              'below' => [
                '1.1.1' => [
                  'below_type' => '',
                  'below' => [],
                ],
                '1.1.2' => [
                  'below_type' => '',
                  'below' => [],
                ],
              ],
            ],
          ],
        ],
      ],
    ], $toc->getTree());

    // Check missing parent.
    $toc = new Toc('<h2>header 2</h2><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', []);
    $this->assertArraySubset([
      'below_type' => 'decimal',
      'below' => [
        '1.0.0' => [
          'below_type' => 'decimal',
          'below' => [
            '1.0.1' => [],
            '1.0.2' => [],
          ],
        ],
      ],
    ], $toc->getTree());

    // $this->dumpArraySubset($toc->getTree(), '$toc->getTree()');
  }

  /**
   * Tests converting table of contents index to hierarchical tree.
   *
   * @see Toc::getContent()
   */
  public function testContent() {
    // Check update content ids.
    $toc = new Toc('<h2>header 2</h2><h3 id="three" class="custom">header 3</h3><h4 id="four">header 4</h4><h4 id="four">header 4</h4><h2>header 2</h2>', []);
    $content = $toc->getContent();
    $this->assertContains('<h2 id="header-2">', $content);
    $this->assertContains('<h3 id="three" class="custom">', $content);
    $this->assertContains('<h4 id="four">', $content);
    $this->assertContains('<h4 id="four-01">', $content);
  }

  /**
   * Tests converting table of contents index to hierarchical tree.
   *
   * @see Toc::getHeaderCount()
   */
  public function testHeaderCount() {
    // Check TOC is hidden.
    $toc = new Toc('<h2>header 2</h2>', []);
    $this->assertFalse($toc->isVisible());
    $this->assertEquals($toc->getHeaderCount(), 1);

    // Check TOC is visible.
    $toc = new Toc('<h2>header 2</h2><h3 id="three" class="custom">header 3</h3><h4 id="four">header 4</h4><h4 id="four">header 4</h4><h2>header 2</h2>', []);
    $this->assertTrue($toc->isVisible());
    $this->assertEquals($toc->getHeaderCount(), 2);
  }

  /**
   * Dumps TOC array into $this->assertArraySubset() assertion.
   *
   * @param array $array
   *   A TOC index or tree.
   * @param string $method
   *   The TOC method to tests.
   */
  protected function dumpArraySubset(array $array, $method) {
    $this->dumpArraySubsetUnset($array);
    $var = Variable::export($array);
    $var = str_replace('array(', '[', $var);
    $var = str_replace('),', '],', $var);
    $var = preg_replace('/\)$/', ']', $var);
    print "\n\n\$this->assertArraySubset($var, $method);\n\n";
  }

  /**
   * Unsets unwanted array properties.
   *
   * @param array $array
   *   A TOC index or tree.
   */
  protected function dumpArraySubsetUnset(array &$array) {
    foreach ($array as &$value) {
      if (!is_array($value)) {
        continue;
      }

      if (isset($value['html'])) {
        unset($value['html']);
      }
      if (isset($value['url'])) {
        unset($value['url']);
      }
      $this->dumpArraySubsetUnset($value);
    }
  }

}
