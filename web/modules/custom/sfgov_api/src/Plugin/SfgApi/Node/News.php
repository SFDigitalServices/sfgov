<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_news",
 *   title = @Translation("Node news"),
 *   bundle = "news",
 *   wag_bundle = "News",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class News extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'headline' => $entity->get('title')->value,
      'date' => $this->convertDateFromFormat('Y-m-d\TH:i:s', $entity->get('field_date')->value),
      'abstract' => $entity->get('field_abstract')->value,
      'body' => $entity->get('body')->value,
      'news_type' => $this->editFieldValue($entity->get('field_news_type')->value, ['news' => 'press_release']),
      'image' => $this->getReferencedEntity($entity->get('field_image')->referencedEntities()),
    ];
  }

}
