<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_acuity_embed",
 *   title = @Translation("Paragraph acuity_embed"),
 *   bundle = "acuity_embed",
 *   wag_bundle = "acuity_embed",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {},
 *   shape = {},
 * )
 */
class AcuityEmbed extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    // @todo this plugin is only fetching data. needs to be massaged.
    return [
      'field_form_id' => $entity->get('field_form_id')->value,
      'field_form_type' => $entity->get('field_form_type')->value,
      'field_token_field_name' => $entity->get('field_token_field_name')->value,
      'field_unauthorized_url' => $entity->get('field_unauthorized_url')->value,
      'field_verification_url' => $entity->get('field_verification_url')->value,
    ];
  }

}
