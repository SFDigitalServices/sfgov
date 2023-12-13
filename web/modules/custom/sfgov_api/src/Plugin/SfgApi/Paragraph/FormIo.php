<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_form_io",
 *   title = @Translation("Paragraph form_io"),
 *   bundle = "form_io",
 *   wag_bundle = "form_io",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class FormIo extends SfgApiParagraphBase {

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'formio_data_source' => $entity->get('field_formio_data_source')->value,
      // @todo this plugin is incomplete
      // 'field_custom_form_strings' => $entity->get('field_custom_form_strings')->value,
      // 'field_form_strings' => $entity->get('field_form_strings')->value,
      // 'field_formio_confirmation_url' => $entity->get('field_formio_confirmation_url')->value,
      // 'field_formio_json_content' => $entity->get('field_formio_json_content')->value,
      // 'field_formio_page_layout' => $entity->get('field_formio_page_layout')->value,
      // 'field_formio_render_options' => $entity->get('field_formio_render_options')->value,
      // 'field_get_formio_strings' => $entity->get('field_get_formio_strings')->value,
    ];
  }

}
