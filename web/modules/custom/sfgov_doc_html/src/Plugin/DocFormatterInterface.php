<?php

namespace Drupal\sfgov_doc_html\Plugin;

/**
 * Defines an interface for the doc_formatter plugin.
 */
interface DocFormatterInterface {

  /**
   * Returns the ID of the plugin.
   *
   * @return string
   *   The plugin ID.
   */
  public function getId(): string;

  /**
   * Returns the label for the plugin.
   *
   * @return string
   *   The plugin label.
   */
  public function getLabel(): string;

  /**
   * Returns the description for the plugin.
   *
   * @return string
   *   The plugin description.
   */
  public function getDescription(): string;

  /**
   * Formats DOM nodes.
   *
   * @param \DOMDocument $document
   *   The full doc DOMDocument.
   *
   * @return \DOMDocument
   *   The formatter doc DOMDocument.
   */
  public function format(\DOMDocument &$document);

}
