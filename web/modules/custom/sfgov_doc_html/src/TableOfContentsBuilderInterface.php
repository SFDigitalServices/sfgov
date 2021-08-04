<?php

namespace Drupal\sfgov_doc_html;

use Drupal\node\NodeInterface;

/**
 * Provides an interface of TableOfContentsBuilder classes.
 */
interface TableOfContentsBuilderInterface {

  /**
   * @param \Drupal\node\NodeInterface $entity
   *   The supported node entity. @see sfgov_doc_html_supported_content_types().
   * @param array $build
   *   A renderable array representing the entity content.
   * @param string $field
   *   The name of the field to attach the TOC to.
   *
   * @return void
   */
  public function attach(NodeInterface $entity, array &$build, string $field = "body");

}
