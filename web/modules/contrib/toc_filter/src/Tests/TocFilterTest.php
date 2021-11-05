<?php

/**
 * @file
 * Definition of Drupal\toc_filter\Tests\TocFilterTest.
 *
 * Copied from \Drupal\filter\Tests\FilterUnitTest.
 */

namespace Drupal\toc_filter\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\RenderContext;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\filter\FilterPluginCollection;

/**
 * Tests TOC filter.
 *
 * @group TocFilter
 */
class TocFilterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'filter', 'toc_api', 'toc_filter', 'toc_filter_test'];

  /**
   * The TOC filter.
   *
   * @var \Drupal\filter\Plugin\FilterInterface
   */
  protected $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Get \Drupal\toc_filter\Plugin\Filter\TocFilter object.
    $filter_bag = new FilterPluginCollection(\Drupal::service('plugin.manager.filter'), ['toc_filter']);
    $this->filter = $filter_bag->get('toc_filter');
  }

  /**
   * Tests the filter processing.
   */
  public function testFilterProcessing() {
    $tests = [
      // Check basic processing.
      '<p>[toc]</p><h2>Header</h2><h2>Header</h2>' => [
        'toc-filter' => TRUE,
      ],
      // Check toc_filter_toc_filter_alter().
      '<p>[toc cancel]</p><h2>Header</h2><h2>Header</h2>' => [
        'toc-filter' => FALSE,
      ],
      // Check hidden.
      '<p>[toc]</p><h2>Header</h2>' => [
        'toc-filter' => FALSE,
      ],
      // Check no token.
      '<h2>Header</h2><h2>Header</h2>' => [
        'toc-filter' => FALSE,
      ],
    ];
    $this->assertFilteredString($this->filter, $tests);

    $configuration = $this->filter->getConfiguration();
    $configuration['settings']['auto'] = 'top';
    $this->filter->setConfiguration($configuration);
    $tests = [
      // Check auto top.
      '<h2>Header</h2><h2>Header</h2>' => [
        'toc-filter' => TRUE,
      ],
    ];
    $this->assertFilteredString($this->filter, $tests);
  }

  /****************************************************************************/
  //  Copied from: Drupal\filter\Tests\FilterUnitTest
  /****************************************************************************/

  /**
   * Asserts multiple filter output expectations for multiple input strings.
   *
   * @param FilterInterface $filter
   *   A input filter object.
   * @param array $tests
   *   An associative array, whereas each key is an arbitrary input string and
   *   each value is again an associative array whose keys are filter output
   *   strings and whose values are Booleans indicating whether the output is
   *   expected or not.
   *
   * For example:
   *
   * @code
   * $tests = [
   *   'Input string' => [
   *     '<p>Input string</p>' => TRUE,
   *     'Input string<br' => FALSE,
   *   ],
   * ];
   * @endcode
   */
  protected function assertFilteredString(FilterInterface $filter, array $tests) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $test = function ($input) use ($filter, $renderer) {
      return $renderer->executeInRenderContext(new RenderContext(), function () use ($input, $filter) {
        return $filter->process($input, LANGUAGE_NONE);
      });
    };

    foreach ($tests as $source => $tasks) {
      $result = $test($source)->getProcessedText();
      foreach ($tasks as $value => $is_expected) {
        // Not using assertIdentical, since combination with strpos() is hard
        // to grok.
        $success = $this->assertTrue(strpos($result, $value) !== FALSE, new FormattableMarkup('@source: @value @found found. Filtered result: @result.', [
          '@source' => var_export($source, TRUE),
          '@value' => var_export($value, TRUE),
          '@result' => var_export($result, TRUE),
          '@found' => ($is_expected) ? '' : ' not',
        ]));

        if (!$success) {
          $this->verbose('Source:<pre>' . Html::escape(var_export($source, TRUE)) . '</pre>'
            . '<hr />' . 'Result:<pre>' . Html::escape(var_export($result, TRUE)) . '</pre>'
            . '<hr />' . ($is_expected ? 'Expected:' : 'Not expected:')
            . '<pre>' . Html::escape(var_export($value, TRUE)) . '</pre>'
          );
        }
      }
    }
  }

}
