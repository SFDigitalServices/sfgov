<?php

/**
 * This script reads the translation provider credentials from a non-vcs location
 * and updates the appropriate configuration items with the read credentials
 */
if (!function_exists('_get_secrets')) {
    include dirname(__DIR__) . '/../shared.php';
}

$providerEnv = PANTHEON_ENVIRONMENT === "live" ? "prod" : "staging";
$filesPath = "/code/web/sites/default/files/private/credentials/translation-providers";

$lionbridge = [
    "provider" => "lionbridge",
    "config_item" => "tmgmt.translator.contentapi",
    "config_item_key" => "settings.capi-settings",
    "creds_file" => "$filesPath/lionbridge/lionbridge-$providerEnv.json"
];

$google = [
    "provider" => "google",
    "config_item" => "tmgmt.translator.google_translate",
    "config_item_key" => "settings",
    "creds_file" => "$filesPath/google/google-$providerEnv.json"
];

$providers = [
    $lionbridge,
    $google,
];

foreach($providers as $provider) {
    $status = '';
    $output = [];
    $creds = file_get_contents(
        $_SERVER["HOME"] . $provider["creds_file"]
    );

    exec("drush config-set " . $provider["config_item"] . " " . $provider["config_item_key"] . " --input-format=yaml --value='" . $creds . "' -y 2>&1", $output, $status);
    
    $output = array_map(
        function ($item) {
            return "    - " . trim($item); 
        }, array_filter($output)
    );

    _test_hook_slack_notification(
        $provider["provider"] . " credentials: \n  - exit status: $status \n  - output: \n" . implode("\n", $output)
    );
}
