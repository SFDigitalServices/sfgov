<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_topic",
 *   title = @Translation("Node topic"),
 *   bundle = "topic",
 *   wag_bundle = "Topic",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Topic extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
    // ATTENTION: THIS IS JUST A STARTING POINT, REFINE THESE DOWN TO THE FIELDS YOU ACTUALLY WANT. ALSO THE ->value function won't work for all fields.
      'nid' => $this->get('nid')->value,
      'uuid' => $this->get('uuid')->value,
      'vid' => $this->get('vid')->value,
      'langcode' => $this->get('langcode')->value,
      'type' => $this->get('type')->value,
      'revision_timestamp' => $this->get('revision_timestamp')->value,
      'revision_uid' => $this->get('revision_uid')->value,
      'revision_log' => $this->get('revision_log')->value,
      'status' => $this->get('status')->value,
      'uid' => $this->get('uid')->value,
      'title' => $this->get('title')->value,
      'created' => $this->get('created')->value,
      'changed' => $this->get('changed')->value,
      'promote' => $this->get('promote')->value,
      'sticky' => $this->get('sticky')->value,
      'default_langcode' => $this->get('default_langcode')->value,
      'revision_default' => $this->get('revision_default')->value,
      'revision_translation_affected' => $this->get('revision_translation_affected')->value,
      'moderation_state' => $this->get('moderation_state')->value,
      'metatag' => $this->get('metatag')->value,
      'path' => $this->get('path')->value,
      'publish_on' => $this->get('publish_on')->value,
      'unpublish_on' => $this->get('unpublish_on')->value,
      'publish_state' => $this->get('publish_state')->value,
      'unpublish_state' => $this->get('unpublish_state')->value,
      'reviewer' => $this->get('reviewer')->value,
      'translation_outdated' => $this->get('translation_outdated')->value,
      'translation_notes' => $this->get('translation_notes')->value,
      'menu_link' => $this->get('menu_link')->value,
      'content_translation_source' => $this->get('content_translation_source')->value,
      'content_translation_outdated' => $this->get('content_translation_outdated')->value,
      'field_content' => $this->get('field_content')->value,
      'field_content_top' => $this->get('field_content_top')->value,
      'field_departments' => $this->get('field_departments')->value,
      'field_department_services' => $this->get('field_department_services')->value,
      'field_description' => $this->get('field_description')->value,
      'field_page_design' => $this->get('field_page_design')->value,
      'field_resources' => $this->get('field_resources')->value,
      'field_spotlight' => $this->get('field_spotlight')->value,
      'field_topics' => $this->get('field_topics')->value,
      'field_top_level_topic' => $this->get('field_top_level_topic')->value,
    ];
  }

}
