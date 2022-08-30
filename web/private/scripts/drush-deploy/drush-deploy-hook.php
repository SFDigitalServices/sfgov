<?php

require dirname(__DIR__) . '/../shared.php';

// Deploy hooks
echo "Running deploy:hook\n";
passthru('drush deploy:hook -y');
echo "Deploy hooks complete\n";

_test_hook_slack_notification("deploy:hook executed");
