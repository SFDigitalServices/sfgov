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
      'news_type' => $this->fixNewsType($entity->get('field_news_type')->value),
      'image' => $this->getReferencedEntity($entity->get('field_image')->referencedEntities()),
    ];
  }

  /**
   * Fix the news type. 'Press release' should be 'press_release'.
   *
   * @param string $value
   *   The value to fix.
   *
   * @return string
   *   The fixed value.
   */
  public function fixNewsType($value) {
    if ($value === 'press release') {
      return 'press_release';
    }
    else {
      return $value;
    }
  }

}
