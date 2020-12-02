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
