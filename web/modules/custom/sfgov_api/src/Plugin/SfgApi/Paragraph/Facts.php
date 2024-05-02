<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_facts",
 *   title = @Translation("Paragraph facts"),
 *   bundle = "facts",
 *   wag_bundle = "facts",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class Facts extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'title' => $entity->get('field_title')->value,
      'facts' => $this->getReferencedData($entity->get('field_facts')->referencedEntities()),
    ];
  }

}
