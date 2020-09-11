<?php

function _get_secrets($requiredKeys, $defaults = [])
{
  $secretsFile = $_SERVER['HOME'] . '/files/private/secrets.json';
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

function _post_json($url, $headers=[], $body='')
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

  $status = $info[CURLINFO_HTTP_CODE];
  $header_size = $info[CURLINFO_HEADER_SIZE];
  $header_string = substr($res, 0, $header_size);
  $headers = array_reduce(explode("\n", $header_string), function ($h, $str) {
    list($key, $value) = preg_split(': +', $str, 2);
    $h[strtolower($key)] = $value;
    return $h;
  }, []);
  $body = substr($res, $header_size);

  return [
    'status' => $status,
    'headers' => $headers,
    'body' => $body
  ];
}

?>
