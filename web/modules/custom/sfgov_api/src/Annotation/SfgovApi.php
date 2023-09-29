<?php

namespace Drupal\sfgov_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines sfgov_api annotation object.
 *
 * @Annotation
 */
class SfgovApi extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The bundle being queried.
   *
   * @var string
   */
  public $bundle;

  /**
   * The entity ID being queried.
   *
   * @var string
   */
  public $entity_id;

  /**
   * The language code being queried.
   *
   * @var string
   */
  public $langcode;

}
