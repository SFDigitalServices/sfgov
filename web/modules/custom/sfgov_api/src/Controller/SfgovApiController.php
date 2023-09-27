<?php

namespace Drupal\sfgov_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Returns responses for Sfgov api routes.
 */
class SfgovApiController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function data() {
    $json_array = [
      'data' => []
    ];
    $nids = \Drupal::entityQuery('node')->condition('type','step_by_step')->execute();
    $nodes = Node::loadMultiple($nids);

    foreach ($nodes as $node) {
      $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/'.$node->id());
      $temp = 'temp';
      $created = DrupalDateTime::createFromTimestamp($node->get('created')->value);
      $changed = DrupalDateTime::createFromTimestamp($node->get('changed')->value);
      $type = $node->get('type')->target_id;
      $id = $node->get('nid')->value;

      $json_array['data'][] = [
        "url" => $url,
        "parent" => $temp, // 2
        "html_path" => $temp, //"http://localhost/budget-process-timeline/"
        "detail_url" => $temp, // "https://api.staging.dev.sf.gov/api/cms/sf.StepByStep/98"
        "path" => $temp, // "000100020010"
        "depth" => $temp, // 3
        "numchild" => $temp, // 0
        "translation_key" => $temp, // "83a4bb33-7fec-4db4-bc22-ad803d8c1c7d",
        "live" => true,
        "has_unpublished_changes" => false,
        "first_published_at" => $created->format('Y-m-d\TH:i:s.uP'),
        "last_published_at" => $changed->format('Y-m-d\TH:i:s.uP'),
        "go_live_at" => null,
        "expire_at" => null,
        "expired" => false,
        "locked" => false,
        "locked_at" => null,
        "title" => $node->getTitle(),
        "draft_title" => $node->getTitle(),
        "slug" => $temp, // "budget-process-timeline",
        "url_path" => $temp, // "/home/budget-process-timeline/",
        "seo_title" => "",
        "show_in_menus" => false,
        "search_description" => "",
        "latest_revision_created_at" => $temp, // "2023-08-15T09:08:00.382823-07:00",
        "description" => $node->get('field_description')->value,
        "intro" => $node->get('field_intro_text')->value,
        "steps" => $temp, // leaving out references for now
        "locale" => $temp, // "https://api.staging.dev.sf.gov/api/cms/locales/1",
        "locked_by" => null,
        "alias_of" => null,
        "aliases" => [
            $temp, // "https://api.staging.dev.sf.gov/api/cms/pages/99"
        ],
        "formsubmission_set" => [],
        "redirect_set" => [],
        "address_set" => [],
        "relatedcontentagency_set" => [],
        "related_content_agencies" => [],
        "relatedcontentpage_set" => [],
        "related_content_pages" => [],
        "relatedcontentpartof_set" => [],
        "related_content_part_of" => [],
        "relatedcontenttopic_set" => [],
        "related_content_topics" => [],
      ];
    }
    return new JsonResponse($json_array);
  }

}
