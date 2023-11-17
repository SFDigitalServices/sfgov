<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_cost",
 *   title = @Translation("Paragraph Cost"),
 *   bundle = "cost",
 *   wag_bundle = "cost",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Cost extends SfgApiParagraphBase {

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'description' => $entity->get('field_text')->value,
      'cost' => $entity->get('field_cost_type')->value,
      'flat_fee' => $entity->get('field_cost_flat_fee')->value,
      'range' => [
        'maximum' => $entity->get('field_cost_maximum')->value,
        'minimum' => $entity->get('field_cost_minimum')->value,
      ],
    ];
  }

}
