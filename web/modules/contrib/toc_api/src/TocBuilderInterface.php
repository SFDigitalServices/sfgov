<?php

/**
 * @file
 * Contains \Drupal\toc_api\TocBuilderInterface.
 */

namespace Drupal\toc_api;

/**
 * Provides an interface defining a TOC builder.
 */
interface TocBuilderInterface {

  /**
   * Renders a table of contents' body.
   *
   * @param \Drupal\toc_api\TocInterface $toc
   *   A TOC object.
   *
   * @return string
   *   The table of content's body content with bookmarked, typed, and custom
   *   headers with back to top links.
   */
  public function renderContent(TocInterface $toc);

  /**
   * Build a table of contents' body.
   *
   * @param \Drupal\toc_api\TocInterface $toc
   *   A TOC object.
   *
   * @return array
   *   A render array containing the table of content's body content with bookmarked, typed, and custom
   *   headers with back to top links.
   */
  public function buildContent(TocInterface $toc);

  /**
   * Renders a table of contents navigation.
   *
   * @param \Drupal\toc_api\TocInterface $toc
   *   A TOC object.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered table of contents.
   */
  public function renderToc(TocInterface $toc);

  /**
   * Builds a table of contents navigation.
   *
   * @param \Drupal\toc_api\TocInterface $toc
   *   A TOC object.
   *
   * @return array
   *   A render array containing a table of contents.
   */
  public function buildToc(TocInterface $toc);

}
