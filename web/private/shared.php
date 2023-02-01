<?php

function _get_secrets($requiredKeys, $defaults = [])
{
  // $secretsFile = $_SERVER['HOME'] . '/files/private/secrets.json';
  $secretsFile = $_SERVER['HOME'] . '/code/web/sites/default/files/private/secrets.json'; // uncomment to test locally with lando drush scr

  if (!file_exists($secretsFile)) {
    die('No secrets file found. Aborting!');
  }
  $secretsContents = file_get_contents($secretsFile);
  $secrets = json_decode($secretsContents, 1);
  if ($secrets == FALSE) {
    die('Could not parse json in secrets file. Aborting!');
  }
  $secrets += $defaults;
  $missing = array_diff($requiredKeys, array_keys($secrets));
  if (!empty($missing)) {
    die('Missing required keys in json secrets file: ' . implode(',', $missing) . '. Aborting!');
  }
  return $secrets;
}

function _curl_post($url, $headers=[], $body='')
{
  $curl = curl_init();
  curl_setopt_array(
    $curl,
    [
      CURLOPT_URL => $url,
      CURLOPT_POST => 1,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_TIMEOUT => 5,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_POSTFIELDS => $body
    ]
  );
  $res = curl_exec($curl);
  $info = curl_getinfo($curl);
  curl_close($curl);

  $status = $info['http_code'];
  $header_size = $info['header_size'];
  $header_string = substr($res, 0, $header_size);
  $headers = array_reduce(explode("\n", $header_string), function ($h, $str) {
    if (str_contains($str, ':')) {
      list($key, $value) = preg_split(': +', $str, 2);
      $h[strtolower($key)] = $value;
    }
    return $h;
  }, []);
  $body = substr($res, $header_size);

  return [
    'status' => $status,
    'headers' => $headers,
    'body' => $body
  ];
}

/**
 * Make a curl get request
 * 
 * @param string $url  url to curl
 * @param array $headers  headers needed to make the request
 */
function _curl_get($url, $headers = []) 
{
  // create curl resource
  $ch = curl_init();

  // set url
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  //return the transfer as a string
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // $output contains the output string
  $output = curl_exec($ch);

  // close curl resource to free up system resources
  curl_close($ch);

  return json_decode($output);
}

/**
 * Send a notification to slack
 */
function _slack_notification($slack_url, $channel, $username, $text, $attachment, $alwaysShowText = false)
{
  $attachment['fallback'] = $text;
  $post = array(
    'username' => $username,
    'channel' => $channel,
    'icon_emoji' => ':pantheon2:',
    'attachments' => array($attachment),
  );
  if ($alwaysShowText) {
    $post['text'] = $text;
  }
  $payload = json_encode($post);
  _curl_post($slack_url, ['Content-Type: application/json'], $payload);
}

function _test_hook_slack_notification($message = '')
{
  $defaults = array(
    // 'slack_channel' => '#proj-sfdotgov-eng',
    'slack_channel' => '#ant-test',
    'slack_username' => 'pantheon-quicksilver',
    'always_show_text' => false,
  );
  
  $secrets = _get_secrets(array('slack_url','github'), $defaults);
  $envMessage = 'environment: ' . (!empty($_ENV['PANTHEON_ENVIRONMENT']) ? $_ENV['PANTHEON_ENVIRONMENT'] : 'none');
  $workflowMessage = 'workflow type: ' . (!empty($_POST['wf_type']) ? $_POST['wf_type'] : 'none');
  $message = $envMessage . "\n" . $workflowMessage . "\n" . $message;
  
  $attachment = array(
    'pretext' => 'test message',
    'text' => '```'. $message .'```'
  );
  
  _slack_notification($secrets['slack_url'], $secrets['slack_channel'], $secrets['slack_username'], $message, $attachment, $secrets['always_show_text']);
}
