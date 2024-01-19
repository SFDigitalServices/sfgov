<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_image",
 *   title = @Translation("Paragraph image"),
 *   bundle = "image",
 *   wag_bundle = "image",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Image extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
    // Notes:
    // - Fields below are just a starting point, refine these down to the fields you actually want.
    // - The ->value function won't work for all fields and is just there to kickstart the process.
    // - Make sure to manually double check and update the wag_bundle in the annotation above.
    // - To small adjustments to the data only relevant to this entity, you add functions to this plugin.
    // - Look at ApiFieldHelperTrait.php for broad functions that can be used across all entities (like entity references).
      'id' => $entity->get('id')->value,
      'uuid' => $entity->get('uuid')->value,
      'revision_id' => $entity->get('revision_id')->value,
      'langcode' => $entity->get('langcode')->value,
      'type' => $entity->get('type')->value,
      'status' => $entity->get('status')->value,
      'created' => $entity->get('created')->value,
      'parent_id' => $entity->get('parent_id')->value,
      'parent_type' => $entity->get('parent_type')->value,
      'parent_field_name' => $entity->get('parent_field_name')->value,
      'behavior_settings' => $entity->get('behavior_settings')->value,
      'default_langcode' => $entity->get('default_langcode')->value,
      'revision_default' => $entity->get('revision_default')->value,
      'revision_translation_affected' => $entity->get('revision_translation_affected')->value,
      'content_translation_source' => $entity->get('content_translation_source')->value,
      'content_translation_outdated' => $entity->get('content_translation_outdated')->value,
      'content_translation_changed' => $entity->get('content_translation_changed')->value,
      'field_image' => $entity->get('field_image')->value,
    ];
  }

}
