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

  public static function linkDependencies(Event $event) 
  {
    $root = static::getDrupalRoot(getcwd());
    $vendor_dir = $event->getComposer()->getConfig()->get('vendor-dir');

    $config_dir_target = $root . '/private/saml/config';
    $config_dir_link = $vendor_dir . '/simplesamlphp/simplesamlphp/config';

    $metadata_dir_target = $root . '/private/saml/metadata';
    $metadata_dir_link = $vendor_dir . '/simplesamlphp/simplesamlphp/metadata';

    // remove vendor simplesamlphp config dir before linking
    rmdir($config_dir_link);
    symlink($config_dir_target, $config_dir_link);

    // remove vendor simplesamlphp metadata dir before linking
    rmdir($metadata_dir_link);
    symlink($metadata_dir_target, $metadata_dir_link);
  }
}
