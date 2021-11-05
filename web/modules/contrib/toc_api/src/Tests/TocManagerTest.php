<?php

/**
 * @file
 * Definition of Drupal\toc_api\Tests\TocManagerTest.
 */

namespace Drupal\toc_api\Tests;

use Drupal\Core\Render\RenderContext;
use Drupal\simpletest\WebTestBase;

/**
 * Tests TOC API manager.
 *
 * @group TocManager
 */
class TocManagerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'toc_api'];

  /**
   * Tests TOC rendering.
   */
  public function testRender() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    /** @var \Drupal\toc_api\TocManager $toc_manager */
    $toc_manager = \Drupal::service('toc_api.manager');
    /** @var \Drupal\toc_api\TocBuilder $toc_builder */
    $toc_builder = \Drupal::service('toc_api.builder');

    // Create render toc and content functions with context.
    $that = $this;
    $render_toc = function($toc) use ($that, $toc_builder, $renderer) {
      return $renderer->executeInRenderContext(new RenderContext(), function () use ($that, $toc_builder, $toc) {
        $content = $toc_builder->renderToc($toc);
        $this->content = $content;
        $that->verbose($content);
        return $content;
      });
    };
    $render_content = function($toc) use ($that, $toc_builder, $renderer) {
      return $renderer->executeInRenderContext(new RenderContext(), function () use ($that, $toc_builder, $toc) {
        $content = $toc_builder->renderContent($toc);
        $this->content = $content;
        $that->verbose($content);
        return $content;
      });
    };

    // Check get and reset Toc.
    $toc = $toc_manager->create('toc_test', '<h2>header 2</h2>', []);
    $this->assertNotNull($toc_manager->getToc('toc_test'));
    $this->assertIdentical($toc->getIndex(), $toc_manager->getToc('toc_test')->getIndex());
    $toc_manager->reset('toc_test');
    $this->assertNull($toc_manager->getToc('toc_test'));

    // Check default TOC options.
    $toc = $toc_manager->create('toc_test', '<h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', []);
    $render_toc($toc);
    $this->assertTrue($this->cssSelect('.toc-desktop.toc.toc-tree'), 'Toc tree exists');
    $this->assertRaw('<h3>Table of Contents</h3>');
    $this->assertRaw('<a href="#header-2">header 2</a>');
    $this->assertRaw('<a href="#header-3">header 3</a>');
    $this->assertRaw('<a href="#header-4">header 4</a>');
    $this->assertRaw('<a href="#header-4-01">header 4</a>');
    $this->assertRaw('<a href="#header-2-01">header 2</a>');
    $this->assertTrue($this->cssSelect('.toc-mobile.toc.toc-menu'), 'Toc menu exists');
    $this->assertRaw('<option value="">Table of Contents</option>');
    $this->assertRaw('<option value="#header-2">1) header 2</option>');
    $this->assertRaw('<option value="#header-3">1.1) header 3</option>');
    $this->assertRaw('<option value="#header-4">1.1.1) header 4</option>');
    $this->assertRaw('<option value="#header-4-01">1.1.2) header 4</option>');
    $this->assertRaw('<option value="#header-2-01">2) header 2</option>');
    $render_content($toc);
    $this->assertPattern('|<a href="#top" class="back-to-top">Back to top</a>\s+<h2|s', 'Back to top before h2');
    $this->assertPattern('|<a href="#top" class="back-to-top">Back to top</a>\s+$|s', 'Back to top at the bottom');
    $this->assertRaw('<a href="#top" class="back-to-top">Back to top</a>');
    $this->assertRaw('<h2 id="header-2"><span>1) </span>header 2</h2>');
    $this->assertRaw('<h3 id="header-3"><span>1.1) </span>header 3</h3>');
    $this->assertRaw('<h4 id="header-4"><span>1.1.1) </span>header 4</h4>');
    $this->assertRaw('<h4 id="header-4-01"><span>1.1.2) </span>header 4</h4>');
    $this->assertRaw('<h2 id="header-2-01"><span>2) </span>header 2</h2>');

    // Check list style type.
    $options = [
      'headers' => [
        'h2' => ['number_type' => 'decimal'],
        'h3' => ['number_type' => 'lower-alpha'],
        'h4' => ['number_type' => 'lower-roman'],
      ],
    ];
    $toc = $toc_manager->create('toc_test', '<h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', $options);
    $render_toc($toc);
    $this->assertPattern('|<ol class="decimal">.*?<ol class="lower-alpha">.*?<ol class="lower-roman">|s');
    $render_content($toc);
    $this->assertRaw('<h2 id="header-2"><span>1) </span>header 2</h2>');
    $this->assertRaw('<h3 id="header-3"><span>1.a) </span>header 3</h3>');
    $this->assertRaw('<h4 id="header-4"><span>1.a.i) </span>header 4</h4>');
    $this->assertRaw('<h4 id="header-4-01"><span>1.a.ii) </span>header 4</h4>');
    $this->assertRaw('<h2 id="header-2-01"><span>2) </span>header 2</h2>');

    // Check custom attributes and html.
    $toc = $toc_manager->create('toc_test', '<h2 class="custom-class"><b>header</b> 2</h2><h2>header 2</h2>', []);
    $render_toc($toc);
    $this->assertRaw('<a href="#header-2"><b>header</b> 2</a>');
    $this->assertRaw('<option value="#header-2">1) header 2</option>');
    $this->assertRaw('<option value="#header-2-01">2) header 2</option>');
    $render_content($toc);
    $this->assertRaw('<h2 class="custom-class" id="header-2"><span>1) </span><b>header</b> 2</h2>');
    $this->assertRaw('<h2 id="header-2-01"><span>2) </span>header 2</h2>');

    // Check custom back to top.
    $options = [
      'top_label' => 'TOP',
      'top_min' => 3,
      'top_max' => 4,
    ];
    $toc = $toc_manager->create('toc_test', '<h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', $options);
    $render_content($toc);
    $this->assertNoPattern('|<a href="#top" class="back-to-top">TOP</a>\s+<h2|s', 'No back to top before h2');
    $this->assertNoPattern('|<a href="#top" class="back-to-top">TOP</a>\s+$|s', 'No back to top at the bottom');
    $this->assertPattern('|<a href="#top" class="back-to-top">TOP</a>\s+<h3 id="header-3"><span>1.1\) </span>header 3</h3>|s', 'Back to top before h3');
    $this->assertPattern('|<a href="#top" class="back-to-top">TOP</a>\s+<h4 id="header-4"><span>1.1.1\) </span>header 4</h4>|s', 'Back to top before first h4');
    $this->assertPattern('|<a href="#top" class="back-to-top">TOP</a>\s+<h4 id="header-4-01"><span>1.1.2\) </span>header 4</h4>|s', 'Back to top before second h4');

    // Check list style type = 'none' and menu indent.
    $options = [
      'default' => ['number_type' => 'none'],
    ];
    $toc = $toc_manager->create('toc_test', '<h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', $options);
    $render_toc($toc);
    $this->assertRaw('<ol class="none">');
    $render_content($toc);

    // Check unorder list when type = FALSE.
    $options = [
      'number_path' => FALSE,
      'default' => [
        'number_type' => FALSE,
      ],
    ];
    $toc = $toc_manager->create('toc_test', '<h2>header 2</h2><h3>header 3</h3><h4>header 4</h4><h4>header 4</h4><h2>header 2</h2>', $options);
    $render_toc($toc);
    $this->assertNoRaw('<ol');
    $this->assertRaw('<ul');
    $this->assertRaw('<option value="#header-2">header 2</option>');
    $this->assertRaw('<option value="#header-3">-- header 3</option>');
    $this->assertRaw('<option value="#header-4">---- header 4</option>');
    $this->assertRaw('<option value="#header-4-01">---- header 4</option>');
    $render_content($toc);
    $this->assertRaw('<h2 id="header-2">header 2</h2>');
    $this->assertRaw('<h3 id="header-3">header 3</h3>');
    $this->assertRaw('<h4 id="header-4">header 4</h4>');
    $this->assertRaw('<h4 id="header-4-01">header 4</h4>');
  }

}
