<?php

/**
 * @file
 * Contains \Drupal\toc_api\TocInterface.
 */

namespace Drupal\toc_api;

/**
 * Provides an interface defining a TOC.
 */
interface TocInterface {

  /**
   * Returns the unaltered source content.
   *
   * @return string
   *   The unaltered source content.
   */
  public function getSource();

  /**
   * Returns the content with all headers assigned a unique id.
   *
   * @return string
   *   The content with all headers assigned a unique id.
   */
  public function getContent();

  /**
   * Returns the TOC options.
   *
   * @return array
   *   The table of contents options.
   */
  public function getOptions();

  /**
   * Return the table of contents title.
   *
   * @return string
   *   The table of contents title.
   */
  public function getTitle();

  /**
   * Returns an array of allowed tags names.
   *
   * @return array
   *   An array of allowed tags names.
   */
  public function getAllowedTags();

  /**
   * Returns a hierarchical array of headers.
   *
   * @return array
   *   An hierarchical array of headers.
   */
  public function getHeaderCount();

  /**
   * Returns a flat associative array of headers.
   *
   * @return array
   *   A flat associative array of headers.
   */
  public function getIndex();

  /**
   * Returns a hierarchical array of headers.
   *
   * @return array
   *   An hierarchical array of headers.
   */
  public function getTree();

  /**
   * Indicates if this table of contents is displayed in a block.
   *
   * @return bool
   *   TRUE if this table of contents is displayed in a block.
   */
  public function isBlock();

  /**
   * Indicates if this table of contents is visible.
   *
   * @return bool
   *   TRUE if this table of contents is visible.
   */
  public function isVisible();

}
