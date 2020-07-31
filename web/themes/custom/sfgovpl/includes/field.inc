<?php

/**
 *  Implements hook_preprocess_field()
 *
 *  Overrides template settings for the field field_google_map within the eck entity location. The purpose for this override is
 *  to give the field a default value along with its settings for the map to render properly.
 *  Default values for the field are NULLs which causes the template engine to exclude field settings in the rendered array.
 *
 * @param array $variables
 */
function simple_gmap_preprocess_eck_entity(&$variables)  {

  $entity_type = 'location';
  $bundle = 'physical';
  $form_mode = 'default';
  $field_name = 'field_google_map';

  // get field definition
  //$bundle_fields = \Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
  //$field_definition = $bundle_fields['field_google_map'];

  // get field storage configuration
  //$field_setting = \Drupal\field\Entity\FieldStorageConfig::loadByName($entity_type, 'field_google_map');

  $settings = \Drupal::service('entity_type.manager')
    ->getStorage('entity_view_display')
    ->load($entity_type . '.' . $bundle . '.' . $form_mode)
    ->getRenderer($field_name)
    ->getSettings();

  // add field_google_map settings for templating. By default, if the field has no value, the field object in content is null.
  $variables['content']['field_google_map'][0] = array(
    "#theme" => "simple_gmap_output",
    "#include_map" => $settings['include_map'],
    "#include_static_map" => $settings['include_static_map'],
    "#include_link" => $settings['include_link'],
    "#include_text" => $settings['include_text'],
    "#width" => array("#plain_text" => $settings['iframe_width']),
    "#height" => array("#plain_text" =>$settings['iframe_height']),
    "#static_scale" => $settings['static_scale'],
    "#url_suffix" => "1231 stevenson", // give it a default value
    "#zoom" => $settings['zoom_level'],
    "#link_text" => array("#plain_text" => $settings['link_text']),
    "#map_type" => $settings['map_type'],
    "#apikey" => $settings['apikey']
  );

}

/**
 * Implements template_preprocess_field().
 */
function sfgovpl_preprocess_field__paragraph__field_file__other_info_document(&$variables) {
  foreach ($variables['items'] as $key => $value) {
    $variables['items'][$key]['file'] = $value['content']['#options'];
    $variables['items'][$key]['fileurl'] = file_create_url($value['content']['#options']['entity']->field_media_file->entity->getFileUri());
  }
}

/**
 * Implements template_preprocess_field().
 */
function sfgovpl_preprocess_field__paragraph__field_file__agenda_item(&$variables) {
  foreach ($variables['items'] as $key => $value) {
    $variables['items'][$key]['file'] = $value['content']['#options'];
    $variables['items'][$key]['fileurl'] = file_create_url($value['content']['#options']['entity']->field_media_file->entity->getFileUri());
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
