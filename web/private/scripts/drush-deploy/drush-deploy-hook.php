<?php

require dirname(__DIR__) . '/../shared.php';

// Deploy hooks
$exitStatus = null;
$output = [];
$debug = "";

echo "Running deploy:hook\n";
// exec('drush deploy:hook -y', $output, $exitStatus);
exec("drush deploy:hook -y", $output, $exitStatus);
foreach($output as $item) {
  $debug .= $item . "\n";
}
echo "Deploy hooks complete\n";

_test_hook_slack_notification("deploy:hook executed with status $exitStatus:\n$debug");
