<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_cost",
 *   title = @Translation("Paragraph Cost"),
 *   bundle = "cost",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Cost extends SfgApiParagraphBase {

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    // @todo change field names (key) to what wagtail expects.
    return [
      'field_text' => $entity->get('field_text')->value,
      'field_cost_type' => $entity->get('field_cost_type')->value,
      'field_cost_flat_fee' => $entity->get('field_cost_flat_fee')->value,
      'field_cost_maximum' => $entity->get('field_cost_maximum')->value,
      'field_cost_minimum' => $entity->get('field_cost_minimum')->value,
    ];
  }

}
