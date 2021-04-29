<?php

require dirname(__DIR__) . '/../shared.php';

// Run a Drush Site Audit in case someone made a boo boo on Deploy
// @TODO: Duplicate to Hipchat
// ob_start();
// passthru('drush aa --strict=0'); // Run the drush command for site audit
// $site_audit_all = ob_get_contents();
// ob_end_clean();

// Important constants :)
$pantheon_yellow = '#EFD01B';
$slack_cutoff = 4000; // slack has a message size cutoff of 16kb (include json syntax), recommended message size is 4000 chars

// Default values for parameters
$defaults = array(
  'slack_channel' => '#proj-sfdotgov-eng',
  'slack_username' => 'pantheon-quicksilver',
  'always_show_text' => false,
);

// Load our hidden credentials.
// See the README.md for instructions on storing secrets.
$secrets = _get_secrets(array('slack_url','github'), $defaults);

// Build an array of fields to be rendered with Slack Attachments as a table
// attachment-style formatting:
// https://api.slack.com/docs/attachments
$fields = array(
  array(
    'title' => 'Site',
    'value' => $_ENV['PANTHEON_SITE_NAME'],
    'short' => 'true'
  ),
  array( // Render Environment name with link to site, <http://{ENV}-{SITENAME}.pantheon.io|{ENV}>
    'title' => 'Environment',
    'value' => '<http://' . $_ENV['PANTHEON_ENVIRONMENT'] . '-' . $_ENV['PANTHEON_SITE_NAME'] . '.pantheonsite.io|' . $_ENV['PANTHEON_ENVIRONMENT'] . '>',
    'short' => 'true'
  ),
  array( // Render Name with link to Email from Commit message
    'title' => 'By',
    'value' => $_POST['user_email'],
    'short' => 'true'
  ),
  array( // Render workflow phase that the message was sent
    'title' => 'Workflow',
    'value' => ucfirst($_POST['stage']) . ' ' . str_replace('_', ' ',  $_POST['wf_type']),
    'short' => 'true'
  ),
  array(
    'title' => 'View Dashboard',
    'value' => '<https://dashboard.pantheon.io/sites/'. PANTHEON_SITE .'#'. PANTHEON_ENVIRONMENT .'/deploys|View Dashboard>',
    'short' => 'true'
  ),
);

// get the latest release
$latestRelease = _curl_get('https://api.github.com/repos/sfdigitalservices/sfgov/releases/latest', [
  'User-Agent: SFDigitalServices/sfgov',
  'Authorization: token ' . $secrets['github'],
]);

// use the tag name to compare
$tagName = $latestRelease->tag_name;
$tagDate = $latestRelease->published_at;
$sinceDate = date_create($tagDate)->modify('-1 day')->format('Y-m-d\TH:i:s\Z');

$fallbackText = 'sf.gov deployed to ' . $_ENV['PANTHEON_ENVIRONMENT'];

$pretext = ':drupal: deployed to `<http://' . $_ENV['PANTHEON_ENVIRONMENT'] . '-' . $_ENV['PANTHEON_SITE_NAME'] . '.pantheonsite.io|' . $_ENV['PANTHEON_ENVIRONMENT'] . '>` | ';
$pretext .= '<https://dashboard.pantheon.io/sites/'. PANTHEON_SITE .'#'. PANTHEON_ENVIRONMENT .'/deploys|pantheon dashboard>' . "\n";
$pretext .= 'commits since tag/release `'. $tagName . '` (<https://github.com/SFDigitalServices/sfgov/commits/main?since=' . $sinceDate . '|full list>):' . "\n";

// get commits since last tag/release date
$commits = _curl_get('https://api.github.com/repos/sfdigitalservices/sfgov/commits?since=' . $sinceDate, [
  'User-Agent: SFDigitalServices/sfgov',
  'Authorization: token ' . $secrets['github'],
]);

$commitsStr = '';

foreach($commits as $commit) {
  $sha = substr($commit->sha, 0, 5);
  $author = $commit->author ? $commit->author->login : $commit->commit->author->name;
  $message = $commit->commit->message;
  $linebreak = strpos($message, "\n\n");
  $trimmedMessage = $linebreak !== false ? substr($message, 0, $linebreak) : $message;
  $commitsStr .= $sha . ' ' . $trimmedMessage . ' (' . $author . ')' . "\n";
}

$suffix = ':point_up: possibly/probably truncated' . "\n\n";
$suffix .= ':yolo: :all_the_things: :ahhhhhhhhh:';

$charCount = strlen($pretext) + strlen($suffix); // keep count of essential parts of message
$commitsStrLen = strlen($commitsStr); // commits character count

if(($commitsStrLen + $charCount) > $slack_cutoff) { // cutoff exceeded
  $commitsStr = substr($commitsStr, 0, ($slack_cutoff-$charCount)); // truncate commits message
}

$attachment = array(
  'pretext' => $pretext,
  // 'fallback' => $text,
  // 'color' => $pantheon_yellow, // Can either be one of 'good', 'warning', 'danger', or any hex color code
  // 'fields' => $fields,
  'text' => '```'. $commitsStr . '```' . $suffix
);

_slack_notification($secrets['slack_url'], $secrets['slack_channel'], $secrets['slack_username'], $fallbackText, $attachment, $secrets['always_show_text']);
