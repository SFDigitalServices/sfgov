<?php
  use Symfony\Component\Yaml\Yaml;

  require dirname(__DIR__) . '/../shared.php';
  
  // echo PANTHEON_ENVIRONMENT;
  $lbConfigItem = 'tmgmt.translator.contentapi';
  $lbConfigItemKey = 'settings.capi-settings';

  $lbEnv = PANTHEON_ENVIRONMENT === 'live' ? 'prod' : 'staging';
  $lbPath = '/code/web/sites/default';
  $lbCredsContents = file_get_contents($_SERVER['HOME'] . "/code/web/sites/default/files/private/credentials/lionbridge/lionbridge-$lbEnv.yml");

  $lbCreds = Yaml::parse($lbCredsContents);
  $lbCredsStr = json_encode($lbCreds, JSON_UNESCAPED_SLASHES|JSON_HEX_QUOT);

  $status = '';
  $output = [];
  
  exec("drush config-set $lbConfigItem $lbConfigItemKey --input-format=yaml --value='$lbCredsStr' -y 2>&1", $output, $status);
  $output = array_map(function($item) { return "    - " . trim($item); }, array_filter($output));

  _test_hook_slack_notification("lionbridge credentials: \n  - exit status: $status \n  - output: \n" . implode("\n", $output));
