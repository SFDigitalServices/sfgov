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
function sfgovpl_preprocess_field__node__field_related_content(&$variables) {
  foreach ($variables['items'] as $key => $value) {
    $urlObject = !empty($value['content']['#url']) ? $value['content']['#url']->getOptions() : NULL;
    if (!empty($urlObject)) {
      $variables['items'][$key]['content'][] = $urlObject['entity']->get('field_description')->view('full');
    }
  }
}

/**
 * Implements hook_preprocess_video_embed_iframe().
 */
function sfgovpl_preprocess_video_embed_iframe(&$variables) {
  // Get the video title and add it to the iframe.
  $youtube_id = explode("https://www.youtube.com/embed/", $variables['url'])[1];
  $youtube_data = json_decode(file_get_contents('https://www.yoembed?url=https://www.youtube.com/watch?v=' . $youtube_id . '&format=json'));
  $title = $youtube_data->title ? $youtube_data->title . ' Youtube Video' : 'Video from Youtube';
  $variables['attributes']['title'] = [
    'value' => $title,
  ];
}
