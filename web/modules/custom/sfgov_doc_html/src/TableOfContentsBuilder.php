<?php

namespace Drupal\sfgov_doc_html;

use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Drupal\toc_api\Entity\TocType;
use Drupal\toc_api\TocBuilderInterface;
use Drupal\toc_api\TocManagerInterface;

class TableOfContentsBuilder implements TableOfContentsBuilderInterface {

  /**
   * The HTML Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The TOC manager.
   *
   * @var \Drupal\toc_api\TocManagerInterface
   */
  protected $tocManager;

  /**
   * The TOC builder.
   *
   * @var \Drupal\toc_api\TocBuilderInterface
   */
  protected $tocBuilder;

  /**
   * The TocType ID.
   *
   * @var string
   */
  protected $tocType;

  /**
   * TableOfContentsBuilder constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The HTML renderer.
   * @param \Drupal\toc_api\TocManagerInterface $toc_manager
   *   The TOC manager.
   * @param \Drupal\toc_api\TocBuilderInterface $toc_builder
   *   The TOC builder.
   * @param $toc_type
   *   The TocType ID to use.
   */
  public function __construct(RendererInterface $renderer, TocManagerInterface $toc_manager, TocBuilderInterface $toc_builder, $toc_type) {
    assert(is_string($toc_type));
    $this->renderer = $renderer;
    $this->tocManager = $toc_manager;
    $this->tocBuilder = $toc_builder;
    $this->tocType = $toc_type;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(NodeInterface $entity, array &$build, string $field = "body") {
    /** @var \Drupal\toc_api\TocTypeInterface $toc_type */
    $toc_type = TocType::load($this->tocType);
    if (!$toc_type) {
      return;
    }

    $source = (string) $this->renderer->render($build[$field][0]);
    $toc = $this->tocManager->create('sfgov_doc_html', $source, $toc_type->getOptions());

    if (!$toc->isVisible()) {
      return;
    }

    $build[$field][0] = [
      'toc' => $this->tocBuilder->buildToc($toc),
      'content' => $this->tocBuilder->buildContent($toc),
    ];
  }

}
