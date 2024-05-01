<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

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
 *   is_stub = {},
 *   shape = {},
 * )
 */
class FormIo extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is only fetching data. needs to be massaged.
      'formio_data_source' => $entity->get('field_formio_data_source')->value,
      'field_custom_form_strings' => $entity->get('field_custom_form_strings')->getValue(),
      'field_form_strings' => $entity->get('field_form_strings')->getValue(),
      'field_formio_confirmation_url' => $entity->get('field_formio_confirmation_url')->value,
      'field_formio_json_content' => $entity->get('field_formio_json_content')->value,
      'field_formio_page_layout' => $entity->get('field_formio_page_layout')->value,
      'field_formio_render_options' => $entity->get('field_formio_render_options')->value,
      'field_get_formio_strings' => $this->editFieldValue($entity->get('field_get_formio_strings')->value, [
        1 => TRUE,
        0 => FALSE,
      ]),
    ];
  }

}
