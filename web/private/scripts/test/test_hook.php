<?php

require dirname(__DIR__) . '/../shared.php';

$defaults = array(
  // 'slack_channel' => '#proj-sfdotgov-eng',
  'slack_channel' => '#ant-test',
  'slack_username' => 'pantheon-quicksilver',
  'always_show_text' => false,
);


$secrets = _get_secrets(array('slack_url','github'), $defaults);
$message = !empty($_POST['wf_type']) ? $_POST['wf_type'] : 'empty message';

$attachment = array(
  'pretext' => 'test message',
  'text' => '```'. $message .'```'
);

_slack_notification($secrets['slack_url'], $secrets['slack_channel'], $secrets['slack_username'], $fallbackText, $attachment, $secrets['always_show_text']);
