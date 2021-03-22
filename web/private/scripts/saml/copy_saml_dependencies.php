<?php

  $root = defined('DRUPAL_ROOT') ? DRUPAL_ROOT : getcwd() . '/web';
  $vendor_dir = $root . '/../vendor';
  $sso_dir = $root . '/sites/default/files/private/saml/test';

  if (isset($_ENV['TERMINUS_ENV']) && $_ENV['TERMINUS_ENV'] == 'live') {
    echo 'terminus environment:' . $_ENV['TERMINUS_ENV'];
    $sso_dir = $root . '/sites/default/files/private/saml/live';
  }    

  $config_dir_source = $sso_dir . '/config';
  $config_dir_dest = $vendor_dir . '/simplesamlphp/simplesamlphp/config';

  $metadata_dir_source = $sso_dir . '/metadata';
  $metadata_dir_dest = $vendor_dir . '/simplesamlphp/simplesamlphp/metadata';

  $config_exists = file_exists($config_dir_source);
  $metadata_exists = file_exists($metadata_dir_source);

  echo "config_dir_source: " . $config_dir_source . "\n";
  echo "config_dir_dest: " . $config_dir_dest . "\n";
  echo "metadata_dir_source: " . $metadata_dir_source . "\n";
  echo "metadata_dir_dest: " . $metadata_dir_dest . "\n";
  echo "config: " . $config_exists . "\n";
  echo "metadata: " . $metadata_exists . "\n";

  if ($config_exists && $metadata_exists) {
    // the /code/vendor directory is write protected on pantheon, hard link instead
    symlink($config_dir_source, $config_dir_dest);
    symlink($metadata_dir_source, $metadata_dir_dest);
  } else { // debug
    echo "Files not found\n";
  }
