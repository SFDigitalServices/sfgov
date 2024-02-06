<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_button",
 *   title = @Translation("Paragraph button"),
 *   bundle = "button",
 *   wag_bundle = "button",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Button extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    // @todo clean up and add optionality when that is working on the wagtail side.
    $link_data = $entity->get('field_link')[0];
    $url = $link_data->getUrl()->toString();
    $title = $link_data->title;
    $external = $link_data->isExternal();
    $page = NULL;
    $link_to = "url";
    $link_data = [
      'url' => $link_data->getUrl()->toString(),
      'page' => $page,
      'link_to' => $link_to,
      'link_text' => $link_data->title,
    ];
    return $link_data;
  }

}
