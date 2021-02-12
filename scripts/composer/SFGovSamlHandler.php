<?php

namespace SFGovSaml;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SFGovSamlHandler
{
  protected static function getDrupalRoot($project_root)
  {
    return $project_root . '/web';
  }

  public static function copyDependencies(Event $event) 
  {
    $root = static::getDrupalRoot(getcwd());
    $vendor_dir = $event->getComposer()->getConfig()->get('vendor-dir');

    $config_dir_source = $root . '/private/saml/config/';
    $config_dir_dest = $vendor_dir . '/simplesamlphp/simplesamlphp/config';

    $config_file_source = $root . '/private/saml/simplesaml_config.php';
    $config_file_dest = $vendor_dir . '/simplesamlphp/simplesamlphp/config/config.php';

    $metadata_dir_source = $root . '/private/saml/metadata/';
    $metadata_dir_dest = $vendor_dir . '/simplesamlphp/simplesamlphp/metadata';

    exec('cp -r ' . $config_dir_source . ' ' . $config_dir_dest);
    copy($config_file_source, $config_file_dest);
    exec('cp -r ' . $metadata_dir_source . ' ' . $metadata_dir_dest);
  }
}
