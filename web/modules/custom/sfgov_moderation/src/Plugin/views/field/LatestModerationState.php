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

    $node = $this->getEntity($values);
    $moderation_values = $this->nidToModerationField($node->id());

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

  /**
   * Get moderation state of most recent revision by nid.
   *
   * @param string $nid
   *   The node id.
   *
   * @return array
   *   An array of revision values.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function nidToModerationField(string $nid) {

    // Get the id of the latest revision.
    $vid = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getLatestRevisionId($nid);

    /** @var \Drupal\node\Entity\Node $revision */
    $revision = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadRevision($vid);

    // Get State.
    $state = $revision->moderation_state->getValue();

    // Get Reviewer.
    $reviewer = $revision->reviewer->getValue();
    if (isset($reviewer[0]['target_id'])) {
      $account = User::load($reviewer[0]['target_id']);
      $username = $account->getUsername();
    }

    // Get Department.
    $bundle = $revision->bundle();
    $dept_field_name = \Drupal::service('sfgov_moderation.util')->getDepartmentFieldName($bundle);
    if ($revision->hasField($dept_field_name)) {
      $department = $revision->get($dept_field_name)->getValue();

      if(isset($department[0]['target_id'])) {
        $node = Node::load($department[0]['target_id']);
        $department_label = $node->label();
      }
    }

    return [
      'state' => isset($state[0]['value']) ? $state[0]['value'] : '',
      'username' => $username ?? NULL,
      'department' => $department_label ?? NULL,
    ];
  }

}
