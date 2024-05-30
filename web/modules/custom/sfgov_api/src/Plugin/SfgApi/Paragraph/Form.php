<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_form",
 *   title = @Translation("Paragraph form"),
 *   bundle = "form",
 *   wag_bundle = "form",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {},
 *   shape = {},
 * )
 */
class Form extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    // @todo this plugin is only fetching data. needs to be massaged.
    return [
      'field_form_id' => $entity->get('field_form_id')->value,
    ];
  }

}
