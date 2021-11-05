<?php

namespace Drupal\tmgmt_file\Commands;

use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class TmgmtFileCommands extends DrushCommands {

  /**
   * Import XLIFF translation files
   *
   * @param $name
   *   Directory path that is search for *.xlf files or a file name
   *
   * @command tmgmt_translate_import
   * @aliases tmti
   *
   * @throws \Exception
   *   If there is no file or if the file is not accessible, throws an exception.
   */
  public function tmgmtTranslateImport($name) {
    if (!file_exists($name)) {
      // Drush changes the current working directory to the drupal root directory.
      // Also check the current directory.
      if (!file_exists(getcwd() . '/' . $name)) {
        throw new \Exception(dt('@name does not exists or is not accessible.', array('@name' => $name)));
      }
      else {
        // The path is relative to the current directory, update the variable.
        $name = getcwd() . '/' . $name;
      }
    }

    if (is_dir($name)) {
      $this->logger()->notice(dt('Scanning dir @dir.', array('@dir' => $name)));
      $files = \Drupal::service('file_system')->scanDirectory($name, '/.*\.xlf$/');
      if (empty($files)) {
        throw new \Exception(dt('No files found to import in @name.', array('@name' => $name)));
      }
    }
    else {
      // Create the structure expected by the loop below.
      $files = array($name => (object)array('name' => basename($name)));
    }

    $plugin = \Drupal::service('plugin.manager.tmgmt_file.format')->createInstance('xlf');
    foreach ($files as $path => $info) {
      $job = $plugin->validateImport($path);
      if (empty($job)) {
        $this->logger()->error(dt('No translation job found for @filename.', array('@filename' => $info->name)));
        continue;
      }

      if ($job->isFinished()) {
        $this->logger()->warning(dt('Skipping @filename for finished job @name (#@id).', array('@filename' => $info->name, '@name' => $job->label(), '@id' => $job->id())));
        continue;
      }

      try {
        // Validation successful, start import.
        $job->addTranslatedData($plugin->import($path));
        $this->logger()->notice(dt('Successfully imported file @filename for translation job @name (#@id).', array('@filename' => $info->name, '@name' => $job->label(), '@id' => $job->id())));
      }
      catch (\Exception $e) {
        $this->logger()->error(dt('Failed importing file @filename: @error', array('@filename' => $info->name, '@error' => $e->getMessage())));
      }
    }
  }
}
