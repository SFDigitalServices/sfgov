<?php

namespace Drupal\tmgmt\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Translator plugin annotation object.
 *
 * @Annotation
 *
 * @see \Drupal\tmgmt\TranslatorManager
 */
class TranslatorPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the translator.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the translator.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The name of the field formatter class.
   *
   * This is not provided manually, it will be added by the discovery mechanism.
   *
   * @var string
   */
  public $class;

  /**
   * An array with default values for this translator.
   *
   * @var array
   */
  public $default_settings = array();

}
