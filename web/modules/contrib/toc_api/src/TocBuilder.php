<?php

/**
 * @file
 * Contains \Drupal\toc_api\TocBuilder.
 */

namespace Drupal\toc_api;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\RendererInterface;

/**
 * Defines a service that builds and renders a table of contents and update an HTML document's headers.
 */
class TocBuilder implements TocBuilderInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new TocBuilder.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function renderContent(TocInterface $toc) {
    if (!$toc->isVisible()) {
      return $toc->getSource();
    }

    $dom = Html::load($toc->getContent());

    $options = $toc->getOptions();
    $index = $toc->getIndex();
    foreach ($index as $item) {
      // Get DOM node by id.
      $dom_node = $dom->getElementById($item['id']);

      // Get attributes.
      $attributes = [];
      if ($dom_node->hasAttributes()) {
        foreach ($dom_node->attributes as $attribute) {
          $attributes[$attribute->nodeName] = $attribute->nodeValue;
        }
      }

      // Build the fragment (header) node.
      $build = [];
      $header_level = (int) $dom_node->tagName[1];
      if ($header_level >= $options['top_min'] && $header_level <= $options['top_max']) {
        $build['top'] = [
          '#theme' => 'toc_back_to_top',
          '#toc' => $toc,
          '#item' => $item,
        ];
      }
      $build['header'] = [
        '#theme' => 'toc_header',
        '#toc' => $toc,
        '#item' => $item,
        '#attributes' => $attributes,
      ];
      $fragment_node = $dom->createDocumentFragment();
      $fragment_node->appendXML($this->renderer->render($build));

      // Replace the header node.
      $dom_node->parentNode->replaceChild($fragment_node, $dom_node);
    }

    // Append back to top to the bottom.
    if ($options['top_min'] == $options['header_min']) {
      $build = [
        '#theme' => 'toc_back_to_top',
        '#toc' => $toc,
        '#item' => NULL,
      ];
      $fragment_node = $dom->createDocumentFragment();
      $fragment_node->appendXML($this->renderer->render($build));
      $dom->getElementsByTagName('body')->item(0)->appendChild($fragment_node);
    }

    return Html::serialize($dom);
  }

  /**
   * {@inheritdoc}
   */
  public function buildContent(TocInterface $toc) {
    return [
      '#markup' => $this->renderContent($toc),
      '#allowed_tag' => Xss::getAdminTagList(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderToc(TocInterface $toc) {
    if (!$toc->isVisible()) {
      return '';
    }

    $build = $this->buildToc($toc);
    return $this->renderer->render($build);
  }

  /**
   * {@inheritdoc}
   */
  public function buildToc(TocInterface $toc) {
    $options = $toc->getOptions();
    return [
      '#theme' => 'toc_' . $options['template'],
      '#toc' => $toc,
    ];
  }

}
