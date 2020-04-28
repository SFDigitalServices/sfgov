<?php
/**
 * @file
 * Drush integration for Google Analytics Reports module.
 */

use Drupal\google_analytics_reports\GoogleAnalyticsReports;
use GuzzleHttp\Exception\RequestException;

/**
 * Implements hook_drush_command().
 */
function google_analytics_reports_drush_command() {
  $commands = array();

  $commands['google-analytics-reports-fields'] = array(
    'aliases' => array('garf'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'description' => dt('Import Google Analytics fields.'),
    'examples' => array(
      'drush google-analytics-reports-fields' => dt('Import Google Analytics fields for Google Analytics Reports module.'),
    ),
    'callback' => 'drush_google_analytics_reports_fields',
  );

  return $commands;
}

/**
 * Implements drush_MODULE_post_pm_enable().
 */
function drush_google_analytics_reports_post_pm_enable() {
  $modules = func_get_args();
  if (in_array('google_analytics_reports', $modules)) {
    drush_google_analytics_reports_fields();
  }
}

/**
 * Imports Google Analytics Fields.
 */
function drush_google_analytics_reports_fields() {
  try {
    $response = \Drupal::httpClient()->request('GET', 'https://www.googleapis.com/analytics/v3/metadata/ga/columns', ['timeout' => 2.0]);
  }
  catch (RequestException $e) {
    \Drupal::logger('google_analytics_reports')->error('Failed to Google Analytics Column metadata definitions due to "%error".', ['%error' => $e->getMessage()]);
    return;
  }
  if ($response->getStatusCode() == 200) {
    $data = $response->getBody()->getContents();
    if (empty($data)) {
      \Drupal::logger('google_analytics_reports')->error('Failed to Google Analytics Column metadata definitions. Received empty content.');
      return;
    }
    $data = json_decode($data, TRUE);
    // Remove old fields.
    if (\Drupal::database()->schema()->tableExists('google_analytics_reports_fields')) {
      \Drupal::database()->truncate('google_analytics_reports_fields')
        ->execute();
    }
    $google_analytics_reports_settings = \Drupal::config('google_analytics_reports.settings')->get();
    // Save current time as last executed time.
    $google_analytics_reports_settings['metadata_last_time'] = REQUEST_TIME;
    // Save etag identifier. It is used to check updates for the fields.
    // @see https://developers.google.com/analytics/devguides/reporting/metadata/v3/devguide#etag
    if (!empty($data['etag'])) {
      $google_analytics_reports_settings['metadata_etag'] = $data['etag'];
    }

    \Drupal::configFactory()->getEditable('google_analytics_reports.settings')
      ->setData($google_analytics_reports_settings)
      ->save();

    if (!empty($data['items'])) {
      $context = array();
      foreach ($data['items'] as $item) {
        // Do not import deprecated fields.
        if ($item['attributes']['status'] == 'PUBLIC') {
          GoogleAnalyticsReports::saveFields($item, $context);
        }
      }
      drush_log(dt('Imported @count Google Analytics fields.', array('@count' => count($context['results']))), 'success');
    }
    else {
      drush_log(dt('An error has occurred during importing Google Analytics fields.'), 'error');
    }
  }
  else {
    drush_log(dt('There is a error during request to Google Analytics Metadata API: @error', array('@error' => $response->error)), 'error');
  }
}
