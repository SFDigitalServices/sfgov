<?php

namespace SFGovSaml;

use Composer\Script\Event;

class SFGovSamlHandler
{
  protected static function getDrupalRoot($project_root)
  {
    return $project_root . '/web';
  }

  public static function copyDependencies(Event $event) 
  { 
    $root = static::getDrupalRoot(getcwd());
    $sso_dir = $root . '/sites/default/files/private/saml/test';
    $vendor_dir = $event->getComposer()->getConfig()->get('vendor-dir');

    if (isset($_ENV['TERMINUS_ENV']) && $_ENV['TERMINUS_ENV'] == 'live') {
      echo 'terminus environment:' . $_ENV['TERMINUS_ENV'];
      $sso_dir = $root . '/sites/default/files/private/saml/live';
    }    

    $config_dir_source = $sso_dir . '/config';
    $config_dir_dest = $vendor_dir . '/simplesamlphp/simplesamlphp/config';

    $metadata_dir_source = $sso_dir . '/metadata';
    $metadata_dir_dest = $vendor_dir . '/simplesamlphp/simplesamlphp/metadata';

    if (file_exists($config_dir_source) && file_exists($metadata_dir_source)) {
      exec(escapeshellcmd('cp -a ' . $config_dir_source . '/. ' . $config_dir_dest));
      exec(escapeshellcmd('cp -a ' . $metadata_dir_source . '/. ' . $metadata_dir_dest));
    } else {
      echo "Files not found\n";
      echo "config_dir_source: " . $config_dir_source . "\n";
      echo "config_dir_dest: " . $config_dir_dest . "\n";
      echo "metadata_dir_source: " . $metadata_dir_source . "\n";
      echo "metadata_dir_dest: " . $metadata_dir_dest . "\n";
      echo "config: " . $config_exists . "\n";
      echo "metadata: " . $metadata_exists . "\n";
    }
  }
}
