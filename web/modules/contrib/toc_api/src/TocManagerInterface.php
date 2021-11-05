<?php

/**
 * @file
 * Contains \Drupal\toc_api\TocManagerInterface.
 */

namespace Drupal\toc_api;

/**
 * Provides an interface defining a TOC manager.
 */
interface TocManagerInterface {

  /**
   * Constructs a new TOC object.
   *
   * @param string $id
   *   ID used to track the TOC object's instance. Typically, the ID can be
   *   the TOC implementation's module name.
   * @param string $source
   *   The HTML content that contains header tags used to create a table of
   *   contents.
   * @param array $options
   *   (optional) An associative array of options used to generate a table of
   *   contents and bookmarked headers.
   *
   * @return \Drupal\toc_api\TocInterface
   *   A new TOC object.
   */
  public function create($id, $source, array $options = []);

  /**
   * Get the current TOC instance.
   *
   * @param string $id
   *   ID used to track the TOC object's instance. Typically, the ID can be
   *   the TOC implementation's module name.
   *
   * @return \Drupal\toc_api\TocInterface
   *   The current TOC instance.
   */
  public function getToc($id);

  /**
   * Reset the current TOC instance.
   *
   * @param string $id
   *   ID used to track the TOC object's instance. Typically, the ID can be
   *   the TOC implementation's module name.
   */
  public function reset($id = NULL);

}
