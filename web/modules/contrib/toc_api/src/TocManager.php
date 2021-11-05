<?php

/**
 * @file
 * Contains \Drupal\toc_api\TocManager.
 */

namespace Drupal\toc_api;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\toc_api\Entity\TocType;

/**
 * Defines a service that creates and manages table of contents instances.
 */
class TocManager implements TocManagerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The TOC instances.
   *
   * @var \Drupal\toc_api\Toc[]
   */
  protected $tocs = [];

  /**
   * Constructs a new TocManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function create($id, $source, array $options = []) {
    // Merge default TOC type options with passed options.
    /** @var \Drupal\toc_api\TocTypeInterface $default_toc */
    if ($default_toc = TocType::load('default')) {
      $options = NestedArray::mergeDeep($default_toc->getOptions(), $options);
    }

    // Create and store a reference to a new Toc.
    $this->tocs[$id] = new Toc($source, $options);
    return $this->tocs[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function getToc($id) {
    return (isset($this->tocs[$id])) ? $this->tocs[$id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function reset($id = NULL) {
    if ($id === NULL) {
      $this->tocs = [];
    }
    else {
      unset($this->tocs[$id]);
    }
  }

}
