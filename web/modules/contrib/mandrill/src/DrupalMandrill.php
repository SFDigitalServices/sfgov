<?php

namespace Drupal\mandrill;

use Mandrill;

/**
 * Overrides default Mandrill library to provide custom API call function.
 */
class DrupalMandrill extends Mandrill {

  /**
   * The user agent sent to the Mandrill API.
   *
   * @var string
   */
  protected $userAgent;

  /**
   * The timeout length in seconds for requests to the Mandrill API.
   *
   * @var int
   */
  protected $timeout;

  /**
   * {@inheritdoc}
   *
   * Override constructor to remove curl operations.
   */
  public function __construct($apikey = NULL, $timeout = 60) {
    if (!$apikey) {
      throw new Mandrill_Error('You must provide a Mandrill API key');
    }
    $this->apikey = $apikey;

    $this->userAgent = "Mandrill-PHP/1.0.55";
    $this->timeout = $timeout;

    $this->root = rtrim($this->root, '/') . '/';

    $this->templates = new \Mandrill_Templates($this);
    $this->exports = new \Mandrill_Exports($this);
    $this->users = new \Mandrill_Users($this);
    $this->rejects = new \Mandrill_Rejects($this);
    $this->inbound = new \Mandrill_Inbound($this);
    $this->tags = new \Mandrill_Tags($this);
    $this->messages = new \Mandrill_Messages($this);
    $this->whitelists = new \Mandrill_Whitelists($this);
    $this->ips = new \Mandrill_Ips($this);
    $this->internal = new \Mandrill_Internal($this);
    $this->subaccounts = new \Mandrill_Subaccounts($this);
    $this->urls = new \Mandrill_Urls($this);
    $this->webhooks = new \Mandrill_Webhooks($this);
    $this->senders = new \Mandrill_Senders($this);
    $this->metadata = new \Mandrill_Metadata($this);
  }

  /**
   * {@inheritdoc}
   *
   * Override __destruct() to prevent calling curl_close().
   */
  public function __destruct() {}

  /**
   * {@inheritdoc}
   *
   * Override call method to user Drupal's HTTP handling.
   */
  public function call($url, $params) {
    $params['key'] = $this->apikey;
    $params = \Drupal\Component\Serialization\Json::encode($params);

    /* @var $client \GuzzleHttp\Client */
    $client = \Drupal::httpClient();

    $options = array(
      'headers' => array(
        'Content-Type' => 'application/json',
        'User-Agent', $this->userAgent,
      ),
      'body' => $params,
    );

    try {
      $response = $client->post($this->root . $url . '.json', $options);
      // Expected result.
      $data = $response->getBody(TRUE);
      $result = \Drupal\Component\Serialization\Json::decode($data);
    }
    catch (RequestException $e) {
      watchdog_exception('my_module', $e->getMessage());
      throw new Mandrill_HttpError(t('Mandrill API call to %url failed: %msg', array('%url' => $url, '%msg' => $response->error)));
    }

    if ($result === NULL) {
      throw new Mandrill_Error('We were unable to decode the JSON response from the Mandrill API: ' . $response->data);
    }

    if (floor($response->getStatusCode() / 100) >= 4) {
      throw $this->castError($result);
    }

    return $result;
  }

}
