<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_campaign",
 *   title = @Translation("Node campaign"),
 *   bundle = "campaign",
 *   wag_bundle = "Campaign",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class Campaign extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    // Figure out theme.
    $theme_term_id = $entity->get('field_campaign_theme')->target_id;
    $theme_name = strtolower($this->editFieldValue(Term::load($theme_term_id)->get('name')->value,
    [
      'Red' => 'orange',
      'Yellow' => 'orange',
      'Purple' => 'blue',
    ]
  ));

    // Figure out facts.
    $facts = $this->getReferencedData($entity->get('field_top_facts')->referencedEntities());
    $facts_title = $facts ? $facts[0]['value']['title'] : '';
    $fact_items = $facts ? $facts[0]['value']['facts'] : [];

    $logo_image = $entity->get('field_logo')[0]->entity;
    $logo_alt_text = $entity->get('field_logo')[0]->alt;

    return [
      'theme' => $theme_name,
      'spotlight_1' => $this->getReferencedData($entity->get('field_header_spotlight')->referencedEntities()),
      'facts_title' => $facts_title,
      // @todo Blocked by fact_item construction.
      'fact_items' => $fact_items,
      'logo' => $logo_image ? $this->getReferencedEntity([$logo_image], FALSE, TRUE, FALSE, ['alt' => $logo_alt_text]) : [],
      // @todo This one is very complex.
      'additional_content' => $this->getReferencedData($entity->get('field_contents')->referencedEntities()),
      'spotlight_2' => $this->getReferencedData($entity->get('field_spotlight')->referencedEntities()),
      'about_campaign' => $entity->get('field_campaign_about')->value,
      // @todo Blocked by link field issue.
      'related_links' => $this->generateLinks($entity->get('field_links')->getvalue()),
    ];
  }

}
