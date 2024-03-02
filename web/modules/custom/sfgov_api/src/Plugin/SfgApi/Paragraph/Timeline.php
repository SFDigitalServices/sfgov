<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_timeline",
 *   title = @Translation("Paragraph timeline"),
 *   bundle = "timeline",
 *   wag_bundle = "timeline",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Timeline extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'title' => $entity->get('field_timeline_title')->value,
      // @todo remove the link from this paragraph because its usually a separate
      // value in wagtail.
      'link' => $this->generateLinks($entity->get('field_link')->getvalue()),
      'timeline_items' => $this->getReferencedData($entity->get('field_timeline_item')->referencedEntities()),
    ];
  }

}
