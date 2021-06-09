<?php

namespace Drupal\sfgov_moderation\Plugin\views\field;

use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\user\Entity\User;
use Drupal\sfgov_moderation\ModerationUtilService;

/**
 * Field showing moderation details about the latest revision.
 *
 * @ViewsField("latest_moderation_state")
 */
class LatestModerationState extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getEntity($values);

    /** @var \Drupal\sfgov_moderation\ModerationUtilServiceInterface $moderationUtil */
    $moderationUtil = \Drupal::service('sfgov_moderation.util');

    $moderation_values = $moderationUtil->getModerationFields($node);

    $render_array = [];
    foreach ($moderation_values as $key => $moderation_value) {
      $line = [
        $key => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $moderation_value,
        ],
      ];
      array_push($render_array, $line);
    }

    return $render_array;

  }

}
