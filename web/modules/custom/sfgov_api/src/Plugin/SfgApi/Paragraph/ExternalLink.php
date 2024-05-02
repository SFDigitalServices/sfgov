<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_resource_entity",
 *   title = @Translation("Paragraph ExternalLink"),
 *   bundle = "resource_entity",
 *   wag_bundle = "external_link",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class ExternalLink extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    // So far this plugin is technically only needed for departments and
    // we're manually setting that one because the data is so tangled.
    return [];
  }

}
