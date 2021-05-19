<?php

namespace Drupal\sfgov_moderation\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 *
 * @ViewsField("latest_moderation_state")
 */
class LatestModerationState extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    $node = $this->getEntity($values);
    return $this->nidToModeration($node->id());

  }

  /**
   * Get moderation state of most recent revision by nid.
   */
  private function nidToModeration(string $nid) {
    $vid = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getLatestRevisionId($nid);

    $revision = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadRevision($vid);

    $state = $revision->get('moderation_state')->getValue();

    return isset($state[0]['value']) ? $state[0]['value'] : '';
  }

}
