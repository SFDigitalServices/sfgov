<?php

require dirname(__DIR__) . '/../shared.php';

// Deploy hooks
$exitStatus = null;
$output = [];
$debug = "";

echo "Running deploy:hook\n";
exec("drush deploy:hook -y", $output, $exitStatus);
echo "Deploy hooks complete\n";

foreach($output as $item) {
  $debug .= $item . "\n";
}

_test_hook_slack_notification("deploy:hook executed with status $exitStatus:\n$debug");
