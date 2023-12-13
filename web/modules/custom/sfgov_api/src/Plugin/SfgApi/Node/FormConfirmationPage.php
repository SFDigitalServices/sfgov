<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_form_confirmation_page",
 *   title = @Translation("Node form_confirmation_page"),
 *   bundle = "form_confirmation_page",
 *   wag_bundle = "form_confirmation_page",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class FormConfirmationPage extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is incomplete
      'body' => $entity->get('body')->value,
      'field_bann' => $entity->get('field_bann')->value,
      'field_banner_color' => $entity->get('field_banner_color')->value,
      'field_banner_image' => $entity->get('field_banner_image')->value,
      'field_confirmation_sidebar' => $entity->get('field_confirmation_sidebar')->value,
      'field_departments' => $entity->get('field_departments')->value,
      'field_description' => $entity->get('field_description')->value,
      // 'field_form_confirm_page_slug' => $entity->get('field_form_confirm_page_slug')->value,
      // 'field_related_content_single' => $entity->get('field_related_content_single')->value,
      // 'field_step' => $entity->get('field_step')->value,
    ];
  }

}
