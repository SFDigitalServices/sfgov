<?php

/**
 * @file
 * Drush (< 9) integration.
 */

/**
 * Implements hook_drush_command().
 */
function simple_sitemap_drush_command() {
  $items['simple-sitemap-generate'] = [
    'description' => 'Regenerate the XML sitemaps according to the module settings.',
    'callback' => 'drush_simple_sitemap_generate',
    'drupal dependencies' => ['simple_sitemap'],
    'aliases' => ['ssg'],
  ];

  $items['simple-sitemap-rebuild-queue'] = [
    'description' => 'Rebuild the sitemap queue for all sitemap variants.',
    'callback' => 'drush_simple_sitemap_rebuild_queue',
    'drupal dependencies' => ['simple_sitemap'],
    'aliases' => ['ssr'],
  ];

  return $items;
}

/**
 * Callback function for hook_drush_command().
 *
 * Regenerate the XML sitemaps according to the module settings.
 */
function drush_simple_sitemap_generate() {
  \Drupal::service('simple_sitemap.generator')->generateSitemap('drush');
}

/**
 * Callback function for hook_drush_command().
 *
 * Rebuild the sitemap queue for all sitemap variants.
 */
function drush_simple_sitemap_rebuild_queue() {
  \Drupal::service('simple_sitemap.generator')->rebuildQueue();
}
