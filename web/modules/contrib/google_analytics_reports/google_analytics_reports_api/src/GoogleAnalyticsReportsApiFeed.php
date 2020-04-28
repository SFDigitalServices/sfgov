<?php

namespace Drupal\google_analytics_reports_api;

use Drupal\Core\Url;
use Drupal\Core\Cache\CacheableRedirectResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Class GoogleAnalyticsReportsApiFeed.
 *
 * GoogleAnalyticsReportsApiFeed class to authorize access to and request data
 * from the Google Analytics Core Reporting API.
 */
class GoogleAnalyticsReportsApiFeed {

  const OAUTH2_REVOKE_URI = 'https://accounts.google.com/o/oauth2/revoke';
  const OAUTH2_TOKEN_URI = 'https://accounts.google.com/o/oauth2/token';
  const OAUTH2_AUTH_URL = 'https://accounts.google.com/o/oauth2/auth';
  const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly https://www.google.com/analytics/feeds/';

  // Response object.
  public $response;

  // Formatted array of request results.
  public $results;

  // URL to Google Analytics Core Reporting API.
  public $queryPath;

  // Translated error message.
  public $error;

  // Boolean TRUE if data is from the cache tables.
  public $fromCache = FALSE;

  // OAuth access token.
  public $accessToken;

  // OAuth refresh token.
  public $refreshToken;

  // OAuth expiration time.
  public $expiresAt;

  // Host and endpoint of Google Analytics API.
  protected $host = 'www.googleapis.com/analytics/v3';

  // Request header source.
  protected $source = 'drupal';

  // Google authorize callback verifier string.
  protected $verifier;

  // OAuth host.
  protected $oAuthHost = 'www.google.com';

  /**
   * Check if object is authenticated with Google.
   */
  public function isAuthenticated() {
    return !empty($this->accessToken);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($token = NULL) {
    $this->accessToken = $token;
  }

  /**
   * Get the current page url.
   *
   * @return string
   *   - current page url.
   */
  public static function currentUrl() {
    $current_path = \Drupal::service('path.current')->getPath();
    return Url::fromUri('base:/' . $current_path, ['absolute' => TRUE])->toString();
  }

  /**
   * Create a URL to obtain user authorization.
   *
   * The authorization endpoint allows the user to first
   * authenticate, and then grant/deny the access request.
   *
   * @param string $client_id
   *   Client id.
   * @param string $redirect_uri
   *   Redirect uri.
   *
   * @return string
   *   Generated url.
   */
  public function createAuthUrl($client_id, $redirect_uri) {
    $query = [
      'response_type' => 'code',
      'redirect_uri' => $redirect_uri,
      'client_id' => urlencode($client_id),
      'scope' => self::SCOPE,
      'access_type' => 'offline',
      'approval_prompt' => 'force',
    ];

    return Url::fromUri(self::OAUTH2_AUTH_URL, ['query' => $query])->toString();
  }

  /**
   * Authenticate with the Google Analytics API.
   *
   * @param string $client_id
   *   Client id.
   * @param string $client_secret
   *   Client secret.
   * @param string $redirect_uri
   *   Redirect uri.
   * @param string $refresh_token
   *   Referesh token.
   */
  protected function fetchToken($client_id, $client_secret, $redirect_uri, $refresh_token = NULL) {
    if ($refresh_token) {
      $params = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'refresh_token' => $refresh_token,
        'grant_type' => 'refresh_token',
      ];
    }
    else {
      $params = [
        'code' => $_GET['code'],
        'grant_type' => 'authorization_code',
        'redirect_uri' => $redirect_uri,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
      ];
    }

    try {
      $client = new Client();
      $response = $client->post(self::OAUTH2_TOKEN_URI, [
        'form_params' => $params,
      ]);

      $this->response = $response->getBody()->getContents();

      if ($response->getStatusCode() == '200') {
        $decoded_response = json_decode($this->response, TRUE);
        $this->accessToken = $decoded_response['access_token'];
        $this->expiresAt = time() + $decoded_response['expires_in'];
        if (!$refresh_token) {
          $this->refreshToken = $decoded_response['refresh_token'];
        }
      }
      else {
        $error_vars = [
          '@code' => $response->getStatusCode(),
          '@details' => print_r(json_decode($this->response), TRUE),
        ];
        $this->error = t('<strong>Code</strong>: @code, <strong>Error</strong>: <pre>@details</pre>', $error_vars);
        \Drupal::logger('google_analytics_reports_api')->error('<strong>Code</strong>: @code, <strong>Error</strong>: <pre>@details</pre>', $error_vars);
      }
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $this->response = $response->getBody()->getContents();

      $error_vars = [
        '@code' => $response->getStatusCode(),
        '@message' => $e->getMessage(),
        '@details' => print_r(json_decode($this->response), TRUE),
      ];
      $this->error = t('<strong>Code</strong>: @code, <strong>Error</strong>: @message, <strong>Message</strong>: <pre>@details</pre>', $error_vars);
      \Drupal::logger('google_analytics_reports_api')->error('<strong>Code</strong>: @code, <strong>Error</strong>: <pre>@details</pre>', $error_vars);
    }
  }

  /**
   * Complete the authentication process.
   *
   * We got here after being redirected from a successful authorization grant.
   * Fetch the access token.
   *
   * @param string $client_id
   *   Client id.
   * @param string $client_secret
   *   Client secret.
   */
  public function finishAuthentication($client_id, $client_secret, $redirect_uri) {
    $this->fetchToken($client_id, $client_secret, $redirect_uri);
  }

  /**
   * Begin authentication.
   *
   * Allowing the user to grant/deny access to the Google account.
   *
   * @param string $client_id
   *   Client id.
   * @param string $redirect_uri
   *   Redirect uri.
   */
  public function beginAuthentication($client_id, $redirect_uri) {
    $response = new CacheableRedirectResponse($this->createAuthUrl($client_id, $redirect_uri));
    $response->send();
  }

  /**
   * Fetches a fresh access token with the given refresh token.
   *
   * @param string $client_id
   *   Client id.
   * @param string $client_secret
   *   Client secret.
   * @param string $refresh_token
   *   Refresh token.
   */
  public function refreshToken($client_id, $client_secret, $refresh_token) {
    $this->refreshToken = $refresh_token;
    $this->fetchToken($client_id, $client_secret, '', $refresh_token);
  }

  /**
   * OAuth step #1: Fetch request token.
   *
   * Revoke an OAuth2 access token or refresh token. This method will revoke
   * the current access token, if a token isn't provided.
   *
   * @param string|NULL $token
   *   The token (access token or a refresh token) that should be revoked.
   *
   * @return boll
   *   Returns True if the revocation was successful, otherwise False.
   */
  public function revokeToken($token = NULL) {
    if (!$token) {
      $token = $this->refreshToken ? $this->refreshToken : $this->accessToken;
    }

    try {
      $client = new Client();
      $response = $client->post(self::OAUTH2_TOKEN_URI, [
        'form_params' => [
          'token' => $token,
        ],
      ]);

      $this->response = $response->getBody()->getContents();

      if ($response->getStatusCode() == 200) {
        $this->accessToken = NULL;
        return TRUE;
      }
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $this->response = $response->getBody()->getContents();

      $error_vars = [
        '@code' => $response->getStatusCode(),
        '@message' => $e->getMessage(),
        '@details' => print_r(json_decode($this->response), TRUE),
      ];
      $this->error = t('<strong>Code</strong>: @code, <strong>Error</strong>: @message, <strong>Message</strong>: <pre>@details</pre>', $error_vars);
      \Drupal::logger('google_analytics_reports_api')->error('<strong>Code</strong>: @code, <strong>Error</strong>: <pre>@details</pre>', $error_vars);
    }

    return FALSE;
  }

  /**
   * OAuth step #2: Authorize request token.
   *
   * Generate authorization token header for all requests.
   *
   * @return array
   *   Authorization header.
   */
  public function generateAuthHeader($token = NULL) {
    if ($token == NULL) {
      $token = $this->accessToken;
    }
    return ['Authorization' => 'Bearer ' . $token];
  }

  /**
   * OAuth step #3: Fetch access token.
   *
   * Set the verifier property.
   */
  public function setVerifier($verifier) {
    $this->verifier = $verifier;
  }

  /**
   * Set the host property.
   */
  public function setHost($host) {
    $this->host = $host;
  }

  /**
   * Set the queryPath property.
   */
  protected function setQueryPath($path) {
    $this->queryPath = 'https://' . $this->host . '/' . $path;
  }

  /**
   * Public query method for all Core Reporting API features.
   */
  public function query($url, $params = [], $method = 'GET', $headers, $cache_options = []) {
    $params_defaults = [
      'start-index' => 1,
      'max-results' => 1000,
    ];
    $params += $params_defaults;

    // Provide cache defaults if a developer did not override them.
    $cache_defaults = [
      'cid' => NULL,
      'bin' => 'default',
      'expire' => google_analytics_reports_api_cache_time(),
      'refresh' => FALSE,
    ];
    $cache_options += $cache_defaults;

    // Provide a query MD5 for the cid if the developer did not provide one.
    if (empty($cache_options['cid'])) {
      $cache_options['cid'] = 'google_analytics_reports_data:' . md5(serialize(array_merge($params, [$url, $method])));
    }

    $cache = \Drupal::cache($cache_options['bin'])->get($cache_options['cid']);

    if (!$cache_options['refresh'] && isset($cache) && !empty($cache->data) && ($cache->expire > REQUEST_TIME)) {
      $this->response = $cache->data;
      $this->results = json_decode($this->response);
      $this->fromCache = TRUE;
    }
    else {
      $this->request($url, $params, $headers);
    }

    if (empty($this->error)) {
      \Drupal::cache($cache_options['bin'])->set($cache_options['cid'], $this->response, $cache_options['expire']);
    }

    return (empty($this->error));
  }

  /**
   * Execute a query.
   */
  protected function request($url, $params = [], $headers = [], $method = 'GET') {
    $options = [
      'method' => $method,
      'headers' => $headers,
    ];

    if (count($params) > 0) {
      if ($method == 'GET') {
        $url .= '?' . http_build_query($params);
      }
      else {
        $options['data'] = http_build_query($params);
      }
    }

    try {
      $client = new Client();

      if ($method == 'GET') {
        $response = $client->get($url, $options);
      }
      else {
        $response = $client->post($url, $options);
      }

      $this->response = $response->getBody()->getContents();

      if ($response->getStatusCode() == 200) {
        $this->results = json_decode($this->response);
      }
      else {
        $error_vars = [
          '@code' => $response->getStatusCode(),
          '@details' => print_r(json_decode($this->response), TRUE),
        ];
        $this->error = t('<strong>Code</strong>: @code, <strong>Error</strong>: <pre>@details</pre>', $error_vars);
        \Drupal::logger('google_analytics_reports_api')->error('<strong>Code</strong>: @code, <strong>Error</strong>: <pre>@details</pre>', $error_vars);
      }
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $this->response = $response->getBody()->getContents();

      $error_vars = [
        '@code' => $response->getStatusCode(),
        '@message' => $e->getMessage(),
        '@details' => print_r(json_decode($this->response), TRUE),
      ];
      $this->error = t('<strong>Code</strong>: @code, <strong>Error</strong>: @message, <strong>Message</strong>: <pre>@details</pre>', $error_vars);
      \Drupal::logger('google_analytics_reports_api')->error('<strong>Code</strong>: @code, <strong>Error</strong>: <pre>@details</pre>', $error_vars);
    }
  }

  /**
   * Query Management API - Accounts.
   */
  public function queryAccounts($params = [], $cache_options = []) {
    $this->setQueryPath('management/accounts');
    $this->query($this->queryPath, $params, 'GET', $this->generateAuthHeader(), $cache_options);
    return $this;
  }

  /**
   * Query Management API - WebProperties.
   */
  public function queryWebProperties($params = [], $cache_options = []) {
    $params += [
      'account-id' => '~all',
    ];
    $this->setQueryPath('management/accounts/' . $params['account-id'] . '/webproperties');
    $this->query($this->queryPath, $params, 'GET', $this->generateAuthHeader(), $cache_options);
    return $this;
  }

  /**
   * Query Management API - Profiles.
   */
  public function queryProfiles($params = [], $cache_options = []) {
    $params += [
      'account-id' => '~all',
      'web-property-id' => '~all',
    ];
    $this->setQueryPath('management/accounts/' . $params['account-id'] . '/webproperties/' . $params['web-property-id'] . '/profiles');
    $this->query($this->queryPath, $params, 'GET', $this->generateAuthHeader(), $cache_options);

    return $this;
  }

  /**
   * Query Management API - Segments.
   */
  public function querySegments($params = [], $cache_options = []) {
    $this->setQueryPath('management/segments');
    $this->query($this->queryPath, $params, 'GET', $this->generateAuthHeader(), $cache_options);
    return $this;
  }

  /**
   * Query Management API - Goals.
   */
  public function queryGoals($params = [], $cache_options = []) {
    $params += [
      'account-id' => '~all',
      'web-property-id' => '~all',
      'profile-id' => '~all',
    ];
    $this->setQueryPath('management/accounts/' . $params['account-id'] . '/webproperties/' . $params['web-property-id'] . '/profiles/' . $params['profile-id'] . '/goals');
    $this->query($this->queryPath, $params, 'GET', $cache_options);
    return $this;
  }

  /**
   * Query and sanitize report data.
   */
  public function queryReportFeed($params = [], $cache_options = []) {

    // Provide defaults if the developer did not override them.
    $params += [
      'profile_id' => 0,
      'dimensions' => NULL,
      'metrics' => 'ga:sessions',
      'sort_metric' => NULL,
      'filters' => NULL,
      'segment' => NULL,
      'start_date' => NULL,
      'end_date' => NULL,
      'start_index' => 1,
      'max_results' => 10000,
    ];

    $parameters = ['ids' => $params['profile_id']];

    if (is_array($params['dimensions'])) {
      $parameters['dimensions'] = implode(',', $params['dimensions']);
    }
    elseif ($params['dimensions'] !== NULL) {
      $parameters['dimensions'] = $params['dimensions'];
    }

    if (is_array($params['metrics'])) {
      $parameters['metrics'] = implode(',', $params['metrics']);
    }
    else {
      $parameters['metrics'] = $params['metrics'];
    }

    if ($params['sort_metric'] == NULL && isset($parameters['metrics'])) {
      $parameters['sort'] = $parameters['metrics'];
    }
    elseif (is_array($params['sort_metric'])) {
      $parameters['sort'] = implode(',', $params['sort_metric']);
    }
    else {
      $parameters['sort'] = $params['sort_metric'];
    }

    if (empty($params['start_date']) || !is_int($params['start_date'])) {
      // Use the day that Google Analytics was released (1 Jan 2005).
      $start_date = '2005-01-01';
    }
    elseif (is_int($params['start_date'])) {
      // Assume a Unix timestamp.
      $start_date = date('Y-m-d', $params['start_date']);
    }

    $parameters['start-date'] = $start_date;

    if (empty($params['end_date']) || !is_int($params['end_date'])) {
      $end_date = date('Y-m-d');
    }
    elseif (is_int($params['end_date'])) {
      // Assume a Unix timestamp.
      $end_date = date('Y-m-d', $params['end_date']);
    }

    $parameters['end-date'] = $end_date;

    // Accept only strings, not arrays, for the following parameters.
    if (!empty($params['filters'])) {
      $parameters['filters'] = $params['filters'];
    }
    if (!empty($params['segment'])) {
      $parameters['segment'] = $params['segment'];
    }
    $parameters['start-index'] = $params['start_index'];
    $parameters['max-results'] = $params['max_results'];

    $this->setQueryPath('data/ga');
    if ($this->query($this->queryPath, $parameters, 'GET', $this->generateAuthHeader(), $cache_options)) {
      $this->sanitizeReport();
    }
    return $this;
  }

  /**
   * Sanitize report data.
   */
  protected function sanitizeReport() {
    // Named keys for report values.
    $this->results->rawRows = isset($this->results->rows) ? $this->results->rows : [];
    $this->results->rows = [];
    foreach ($this->results->rawRows as $row_key => $row_value) {
      foreach ($row_value as $item_key => $item_value) {
        $field_without_ga = str_replace('ga:', '', $this->results->columnHeaders[$item_key]->name);

        // Allow other modules to alter executed data before display.
        \Drupal::moduleHandler()->alter('google_analytics_reports_api_reported_data', $field_without_ga, $item_value);

        $this->results->rows[$row_key][$field_without_ga] = $item_value;
      }
    }
    unset($this->results->rawRows);

    // Named keys for report totals.
    $this->results->rawTotals = $this->results->totalsForAllResults;
    $this->results->totalsForAllResults = [];
    foreach ($this->results->rawTotals as $row_key => $row_value) {
      $this->results->totalsForAllResults[str_replace('ga:', '', $row_key)] = $row_value;
    }
    unset($this->results->rawTotals);
  }

}
