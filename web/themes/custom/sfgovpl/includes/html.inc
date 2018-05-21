<?php
//
///**
// * @file
// * Theme and preprocess functions for html.
// */
//
///**
// * Implements hook_html_head_alter().
// */
//function hops_html_head_alter(&$head_elements) {
//
//  // HTML5 charset declaration.
//  $head_elements['system_meta_content_type']['#attributes'] = array(
//    'charset' => 'utf-8',
//  );
//
//  // Optimize mobile viewport.
//  $head_elements['mobile_viewport'] = array(
//    '#type' => 'html_tag',
//    '#tag' => 'meta',
//    '#attributes' => array(
//      'name' => 'viewport',
//      'content' => 'width=device-width, initial-scale=1',
//    ),
//  );
//}
//
///**
// * Preprocess variables for html templates.
// *
// * @param $variables
// */
//function hops_preprocess_html(&$variables) {
//  // Add libraries.
//  hops_get_theme($variables);
//
//  // Less is more favicons package
//  // @see https://realfavicongenerator.net/blog/new-favicon-package-less-is-more/
//  // @see http://drupal.stackexchange.com/questions/12834/change-default-favicon-programmatically
//  $favicon_path = base_path() . drupal_get_path('theme', 'hops') . '/images/favicons/';
//
//  $apple_touch_icon = array(
//    '#tag' => 'link',
//    '#attributes' => array(
//      'href' => file_create_url($favicon_path . 'apple-touch-icon.png'),
//      'rel' => 'apple-touch-icon',
//      'sizes' => '180x180',
//    ),
//    '#weight' => 8888,
//  );
//  $variables['#attached']['html_head'][] = [$apple_touch_icon, 'favicon-180'];
//
//  $favicon_sizes = array(16,32);
//  foreach ($favicon_sizes as $size) {
//    $favicon = array(
//      '#tag' => 'link',
//      '#attributes' => array(
//        'href' => file_create_url($favicon_path . 'favicon-' . $size . 'x' . $size . '.png'),
//        'rel' => 'icon',
//        'type' => 'image/png',
//        'sizes' => $size . 'x' . $size,
//      ),
//      '#weight' => 8888,
//    );
//    $variables['#attached']['html_head'][] = [$favicon, 'favicon-' . $size];
//  }
//
//  // Add meta theme-color
//  // @see http://updates.html5rocks.com/2014/11/Support-for-theme-color-in-Chrome-39-for-Android
//  if (theme_get_setting('meta_theme_color')) {
//    $meta_theme_color = array(
//      '#tag' => 'meta',
//      '#attributes' => array(
//        "name" => "theme-color",
//        "content" => theme_get_setting('meta_theme_color'),
//      ),
//      '#weight' => -1,
//    );
//    $variables['#attached']['html_head'][] = [$meta_theme_color, 'meta_theme_color'];
//  }
//
//}
