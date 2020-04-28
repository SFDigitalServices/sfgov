<?php

namespace Drupal\tmgmt_file\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Format plugin annotation object.
 *
 * @Annotation
 *
 * @see \Drupal\tmgmt_file\Format\FormatManager
 */
class FormatPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the format.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
