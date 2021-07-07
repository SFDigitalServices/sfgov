<?php

namespace Drupal\sfgov_doc_html\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a doc_formatter annotation object.
 *
 * @Annotation
 */
class DocFormatter extends Plugin {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The label for the plugin.
   *
   * @var string
   */
  public $label;

  /**
   * The description for the plugin.
   *
   * @var string
   */
  public $description;

}
