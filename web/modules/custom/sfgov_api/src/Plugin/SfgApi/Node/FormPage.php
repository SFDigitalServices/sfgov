<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\node\Entity\Node;
use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_form_page",
 *   title = @Translation("Node form_page"),
 *   bundle = "form_page",
 *   wag_bundle = "form_page",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {
 *     "paragraph_form_io",
 *   },
 * )
 */
class FormPage extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $formio_data_source = $this->getReferencedData($entity->get('field_form_id')->referencedEntities());
    return [
      // @todo this plugin is only fetching data. needs to be massaged.
      'field_formio_json_content' => $entity->get('field_formio_json_content')->value,
      'field_intro_text' => $entity->get('field_intro_text')->value,
      'data_source' => $formio_data_source[0]['value']['formio_data_source'],
      'confirmation_page' => $this->getReferencedEntity([$this->getFormConfirmationPage($entity->id())]),
    ];
  }

  /**
   * Get the form confirmation page for a given form. WIP.
   *
   * @param int $form_id
   *   The form id.
   *
   * @return array
   *   The form confirmation page.
   */
  public function getFormConfirmationPage($form_id) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'form_confirmation_page')
      ->condition('field_related_content_single', $form_id);
    $nids = $query->execute();
    if (count($nids) > 0) {
      $nid = array_shift($nids);
      return Node::load($nid);
    }
    return [];
  }

}
