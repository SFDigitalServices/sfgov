<?php

namespace Drupal\sfgov_doc_html\Plugin;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base class for doc_formatter plugins.
 */
abstract class DocFormatterBase extends PluginBase implements DocFormatterInterface {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): String {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): String {
    return $this->pluginDefinition['description'];
  }

}
