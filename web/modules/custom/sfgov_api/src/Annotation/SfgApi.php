<?php

namespace Drupal\sfgov_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines sfgov_api annotation object.
 *
 * @Annotation
 */
class SfgApi extends Plugin {

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
   * The wagtail bundle this will migrate into.
   *
   * @var string
   */
  public $wag_bundle;

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

  /**
   * The other plugins that this plugin references.
   *
   * @var string
   */
  public $referenced_plugins;

}
