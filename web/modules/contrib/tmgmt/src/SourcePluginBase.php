<?php

namespace Drupal\tmgmt;

use Drupal\Core\Plugin\PluginBase;

/**
 * Default controller class for source plugins.
 *
 * @ingroup tmgmt_source
 */
abstract class SourcePluginBase extends PluginBase implements SourcePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel(JobItemInterface $job_item) {
    return t('@plugin item unavailable (@item)', array('@plugin' => $this->pluginDefinition['label'], '@item' => $job_item->getItemType() . ':' . $job_item->getItemId()));
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(JobItemInterface $job_item) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemTypes() {
    return isset($this->pluginDefinition['item types']) ? $this->pluginDefinition['item types'] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getItemTypeLabel($type) {
    $types = $this->getItemTypes();
    if (isset($types[$type])) {
      return $types[$type];
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getType(JobItemInterface $job_item) {
    return ucfirst($job_item->getItemType());
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingLangCodes(JobItemInterface $job_item) {
    return [$this->getSourceLangCode($job_item)];
  }

}

