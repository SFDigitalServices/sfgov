<?php

/**
 * Implements template_preprocess_field().
 */
function sfgovpl_preprocess_field__paragraph__field_file__agenda_item(&$variables) {
  foreach ($variables['items'] as $key => $value) {
    $variables['items'][$key]['label'] = $value['content']['#options']['entity']->label();
    $variables['items'][$key]['url'] =
      $value['content']['#options']['entity']->field_media_file->entity ?
      file_create_url($value['content']['#options']['entity']->field_media_file->entity->getFileUri()) :
      '';
  }
}

/**
 * Implements template_preprocess_field().
 */
function sfgovpl_preprocess_field__paragraph__field_resources__other_info_card(&$variables) {
  $node = \Drupal::request()->attributes->get('node');
  $variables['nodetype'] = $node ? $node->getType() : '';
}

/**
 * Implements template_preprocess_field().
 */
function sfgovpl_preprocess_field__node__field_related_content(&$variables) {
  foreach ($variables['items'] as $key => $value) {
    $urlObject = !empty($value['content']['#url']) ? $value['content']['#url']->getOptions() : NULL;
    if (!empty($urlObject)) {
      // Check if the output has a description field. some of the content types
      // that are reference-able don't have a description field.
      if ($urlObject['entity']->hasField('field_description')) {
        $variables['items'][$key]['content'][] = $urlObject['entity']->get('field_description')
          ->view('full');
      }
      // Campaigns don't have descriptions, but do have about fields. This setup
      // tries to grab a teaser version of the about field, trimmed down to 100
      // characters.
      elseif ($urlObject['entity']->hasField('field_campaign_about')) {
        $variables['items'][$key]['content'][] = $urlObject['entity']->get('field_campaign_about')
          ->view('teaser');
      }
    }
  }
}

/**
 * Implements hook_preprocess_video_embed_iframe().
 */
function sfgovpl_preprocess_video_embed_iframe(&$variables) {
  // Get the video title and add it to the iframe.
  $youtube_id = explode("https://www.youtube.com/embed/", $variables['url'])[1];
  $video_service = \Drupal::service('sfgov_video.utilities');
  $video_title = $video_service->getVideoTitle($youtube_id);
  $title = $video_title ? $video_title . ' Youtube Video' : 'Video from Youtube';
  $variables['attributes']['title'] = [
    'value' => $title,
  ];
}
