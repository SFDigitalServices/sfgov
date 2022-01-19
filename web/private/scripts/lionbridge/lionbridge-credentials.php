<?php

/**
 * This script reads the lionbridge credentials from a non-vcs location
 * and updates the appropriate lionbridge configuration item with the read credentials
 */

require dirname(__DIR__) . '/../shared.php';

$lbConfigItem = 'tmgmt.translator.contentapi';
$lbConfigItemKey = 'settings.capi-settings';

$lbEnv = PANTHEON_ENVIRONMENT === 'live' ? 'prod' : 'staging';
$filesPath = '/code/web/sites/default/files/private';
$lbCreds = file_get_contents(
    $_SERVER['HOME'] . "$filesPath/credentials/lionbridge/lionbridge-$lbEnv.json"
);

$status = '';
$output = [];

exec("drush config-set $lbConfigItem $lbConfigItemKey --input-format=yaml --value='$lbCreds' -y 2>&1", $output, $status);

// some debug output
$output = array_map(
    function ($item) {
        return "    - " . trim($item); 
    }, array_filter($output)
);
_test_hook_slack_notification(
    "lionbridge credentials: \n  - exit status: $status \n  - output: \n" . implode("\n", $output)
);
