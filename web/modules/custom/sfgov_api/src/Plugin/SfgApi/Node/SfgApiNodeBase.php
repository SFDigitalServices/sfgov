<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;
use Drupal\sfgov_api\SfgApiPluginBase;

/**
 * Base class for sfgov_api node plugins.
 */
abstract class SfgApiNodeBase extends SfgApiPluginBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  protected $entityType = 'node';

  /**
   * {@inheritDoc}
   */
  public function setBaseData($node) {
    $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id());
    $temp = 'temp';

    // @todo All of the comments below are whats in the API. this list needs to be refined.
    $base_data = [
      'url' => $url,
    // 2
      'parent' => $temp,
    // 'http://localhost/budget-process-timeline/'
      'html_path' => $temp,
    // 'https://api.staging.dev.sf.gov/api/cms/sf.StepByStep/98'
      'detail_url' => $temp,
    // '000100020010'
      'path' => $temp,
    // 3
      'depth' => $temp,
    // 0
      'numchild' => $temp,
    // '83a4bb33-7fec-4db4-bc22-ad803d8c1c7d',
      'translation_key' => $temp,
      'live' => TRUE,
      'has_unpublished_changes' => FALSE,
      'first_published_at' => $this->getWagtailTime($node->get('created')->value),
      'last_published_at' => $this->getWagtailTime($node->get('changed')->value),
      'go_live_at' => NULL,
      'expire_at' => NULL,
      'expired' => FALSE,
      'locked' => FALSE,
      'locked_at' => NULL,
      'title' => $node->getTitle(),
      'draft_title' => $node->getTitle(),
    // 'budget-process-timeline',
      'slug' => $temp,
    // '/home/budget-process-timeline/',
      'url_path' => $temp,
      'seo_title' => '',
      'show_in_menus' => FALSE,
      'search_description' => '',
    // '2023-08-15T09:08:00.382823-07:00',
      'latest_revision_created_at' => $temp,
    // 'https://api.staging.dev.sf.gov/api/cms/locales/1',
      'locale' => $temp,
      'locked_by' => NULL,
      'alias_of' => NULL,
      'aliases' => [
    // 'https://api.staging.dev.sf.gov/api/cms/pages/99'
        $temp,
      ],
      'formsubmission_set' => [],
      'redirect_set' => [],
      'address_set' => [],
      'relatedcontentagency_set' => [],
      'related_content_agencies' => [],
      'relatedcontentpage_set' => [],
      'related_content_pages' => [],
      'relatedcontentpartof_set' => [],
      'related_content_part_of' => [],
      'relatedcontenttopic_set' => [],
      'related_content_topics' => [],
    ];

    return $base_data;
  }

}
