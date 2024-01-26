<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;
use Drupal\sfgov_api\SfgApiPluginBase;

/**
 * Base class for sfgov_api node plugins.
 */
abstract class SfgApiNodeBase extends SfgApiPluginBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  protected $entityType = 'node';

  /**
   * {@inheritDoc}
   */
  public function setBaseData($node) {
    $node_id = $node->id();
    $path_alias_manager = \Drupal::service('path_alias.manager');
    $alias = $path_alias_manager->getAliasByPath('/node/' . $node_id);
    $wag_api_settings = \Drupal::config('sfgov_api.settings');
    $slug = $node->bundle() . '__' . basename($alias);
    $base_data = [
      'parent_id' => (int) $wag_api_settings->get('wag_parent_' . $this->configuration['langcode']),
      'title' => $node->getTitle(),
      'slug' => $slug,
      'aliases' => [],
      'formsubmission_set' => [],
      'redirect_set' => [],
      'address_set' => [],
      'relatedcontentagency_set' => [],
      'related_content_agencies' => [],
      'relatedcontentpage_set' => [],
      'related_content_pages' => [],
      'relatedcontentpartof_set' => [],
      'related_content_part_of' => [],
      'relatedcontenttopic_set' => [],
      'related_content_topics' => [],
      'related_information' => [],
      'part_of_locations' => [],
      'related_locations' => [],
      'related_transaction' => [],
    ];

    return $base_data;
  }

}
