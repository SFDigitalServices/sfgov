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
        // SG-1894 Removed the description from campaign about field.
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

/**
 * Implements template_preprocess_field().
 */
function sfgovpl_preprocess_field__node__field_departments(&$variables) {
  $bundle = $variables['element']['#bundle'];
  $variables['label'] = t('Departments', [], ['context' => 'Related departments field label for ' . $bundle]);
}

/**
 * Implements template_preprocess_field().
 */
function sfgovpl_preprocess_field__node__title(&$variables) {
  foreach ($variables['items'] as $key => $value) {
    $variables['items'][$key]['content']['#context']['value'] = _sfgovpl_remove_nbsp($variables['items'][$key]['content']['#context']['value']);
  }
}

/**
 * Implements template_preprocess_field().
 */
function sfgovpl_preprocess_field__paragraph__field_title(&$variables) {
  foreach ($variables['items'] as $key => $value) {
    $variables['items'][$key]['content']['#context']['value'] = _sfgovpl_remove_nbsp($variables['items'][$key]['content']['#context']['value']);
  }
}

/**
 * Implements template_preprocess_field().
 */
function sfgovpl_preprocess_field__paragraph__field_owner(&$variables) {
  foreach ($variables['items'] as $key => $value) {
    $variables['items'][$key]['content']['#context']['value'] = _sfgovpl_remove_nbsp($variables['items'][$key]['content']['#context']['value']);
  }
}

/**
 * Implements template_preprocess_field().
 */
function sfgovpl_preprocess_field__paragraph__field_link(&$variables) {
  $paragraph_title = '';
  $paragraph = $variables['element']['#object'];
  if ($paragraph->hasField('field_title')) {
    $paragraph_title = $paragraph->get('field_title')->getValue()[0]['value'];
  }

  foreach ($variables['items'] as $delta => $item) {
    $link_title = $variables['items'][$delta]['content']['#title'];
    if (empty($variables['items'][$delta]['content']['#options']['attributes']['aria-label'])) {
      $variables['items'][$delta]['content']['#options']['attributes']['aria-label'] = $link_title . ' ' . $paragraph_title;
    }
  }
}