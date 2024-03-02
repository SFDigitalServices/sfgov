<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_public_body_profiles",
 *   title = @Translation("Paragraph public_body_profiles"),
 *   bundle = "public_body_profiles",
 *   wag_bundle = "public_body_profiles",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class PublicBodyProfiles extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is only fetching data. needs to be massaged.
      'field_commission_position' => $entity->get('field_commission_position')->value,
      'field_department' => $this->getReferencedEntity($entity->get('field_department')->referencedEntities()),
      'field_ending_year' => $entity->get('field_ending_year')->value,
      'field_position_type' => $entity->get('field_position_type')->value,
      'field_profile' => $entity->get('field_profile')->value,
      'field_starting_year' => $entity->get('field_starting_year')->value,
      'field_title' => $entity->get('field_title')->value,
    ];
  }

}
