# Config Exclude

Config Exclude allows you to exclude modules and their configuration from being exported. It is an easy way for developers to enable development modules without the risk of accidentally exporting the enabled-state or their dependent config.

See https://www.drupal.org/project/config_split/issues/2926505 to learn why this module was created.

## Usage

Enable the module and activate the filter by declaring $settings['config_exclude_modules'] in your settings.php file, eg:

    $settings['config_exclude_modules'] = ['devel', 'stage_file_proxy'];

Now, when you export configuration (`drush config-export`), the selected modules should no longer show up in core.extension.yml and their configuration should not be exported.