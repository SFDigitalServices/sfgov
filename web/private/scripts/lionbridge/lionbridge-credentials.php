<?php

/**
 * This script reads the lionbridge credentials from a non-vcs location
 * and updates the appropriate lionbridge configuration item with the read credentials
 */

use Symfony\Component\Yaml\Yaml;

require dirname(__DIR__) . '/../shared.php';

$lbConfigItem = 'tmgmt.translator.contentapi';
$lbConfigItemKey = 'settings.capi-settings';

$lbEnv = PANTHEON_ENVIRONMENT === 'live' ? 'prod' : 'staging';
$filesPath = '/code/web/sites/default/files/private';
$lbCredsContents = file_get_contents(
    $_SERVER['HOME'] . "$filesPath/credentials/lionbridge/lionbridge-$lbEnv.yml"
);

$lbCreds = Yaml::parse($lbCredsContents);
$lbCredsStr = json_encode($lbCreds, JSON_UNESCAPED_SLASHES|JSON_HEX_QUOT);

$status = '';
$output = [];

exec("drush config-set $lbConfigItem $lbConfigItemKey --input-format=yaml --value='$lbCredsStr' -y 2>&1", $output, $status);

// some debug output
$output = array_map(
    function ($item) {
        return "    - " . trim($item); 
    }, array_filter($output)
);
_test_hook_slack_notification(
    "lionbridge credentials: \n  - exit status: $status \n  - output: \n" . implode("\n", $output)
);
