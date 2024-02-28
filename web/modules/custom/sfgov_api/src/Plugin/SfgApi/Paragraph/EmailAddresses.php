<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_email_addresses",
 *   title = @Translation("Paragraph email_addresses"),
 *   bundle = "email_addresses",
 *   wag_bundle = "email_addresses",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {
 *      "paragraph_email",
 *   },
 * )
 */
class EmailAddresses extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'field_email_addresses_email' => $this->getReferencedData($entity->get('field_email_addresses_email')->referencedEntities()),
    ];
  }

}
