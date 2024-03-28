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
 *   is_stub = {},
 * )
 */
class EmailAddresses extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'emails' => $this->getReferencedData($entity->get('field_email_addresses_email')->referencedEntities()),
    ];
  }

}
