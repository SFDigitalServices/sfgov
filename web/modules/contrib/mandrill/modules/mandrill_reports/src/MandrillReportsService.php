<?php

/**
 * @file
 * Contains \Drupal\mandrill_reports\MandrillReportsService.
 */

namespace Drupal\mandrill_reports;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\mandrill\MandrillAPIInterface;

/**
 * Mandrill Reports service.
 */
class MandrillReportsService implements MandrillReportsInterface {

  /**
   * The Mandrill API service.
   *
   * @var \Drupal\mandrill\MandrillAPIInterface
   */
  protected $mandrill_api;

  /**
   * The Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs the service.
   *
   * @param \Drupal\mandrill\MandrillAPIInterface $mandrill_api
   *   The Mandrill API service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(MandrillAPIInterface $mandrill_api, ConfigFactoryInterface $config_factory) {
    $this->mandrill_api = $mandrill_api;
    $this->config = $config_factory;
  }

  public function getUser() {
    return $this->mandrill_api->getUser();
  }

  /**
   * Gets tag data formatted for reports.
   *
   * @return array
   */
  public function getTags() {
    $cache = \Drupal::cache('mandrill');
    $cached_tags = $cache->get('tags');

    if (!empty($cached_tags)) {
      return $cached_tags->data;
    }

    $data = array();
    $tags = $this->mandrill_api->getTags();
    foreach ($tags as $tag) {
      if (!empty($tag['tag'])) {
        $data[$tag['tag']] = $this->mandrill_api->getTag($tag['tag']);
        $data[$tag['tag']]['time_series'] = $this->mandrill_api->getTagTimeSeries($tag['tag']);
      }
    }

    $cache->set('tags', $data);

    return $data;
  }

  /**
   * Gets recent history for all tags.
   *
   * @return array
   */
  public function getTagsAllTimeSeries() {
    $cache = \Drupal::cache('mandrill');
    $cached_tags_series = $cache->get('tags_series');

    if (!empty($cached_tags_series)) {
      return $cached_tags_series->data;
    }

    $data = $this->mandrill_api->getTagsAllTimeSeries();

    $cache->set('tags_series', $data);

    return $data;
  }

  /**
   * Gets sender data formatted for reports.
   *
   * @return array
   */
  public function getSenders() {
    $cache = \Drupal::cache('mandrill');
    $cached_senders = $cache->get('senders');

    if (!empty($cached_senders)) {
      return $cached_senders->data;
    }

    $data = array();
    $senders = $this->mandrill_api->getSenders();

    foreach ($senders as $sender) {
      try {
        $data[$sender['address']] = $this->mandrill_api->getSender($sender['address']);
        $data[$sender['address']]['time_series'] = $this->mandrill_api->getSenderTimeSeries($sender['address']);
      }
      catch (\Exception $e) {
        \Drupal::logger('mandrill')->error('An error occurred requesting sender information from Mandrill for address %address. "%message"', array(
          '%address' => $sender['address'],
          '%message' => $e->getMessage(),
        ));
      }
    }

    $cache->set('senders', $data);

    return $data;
  }

  /**
   * Gets URLs formatted for reports.
   *
   * @return array
   */
  public function getUrls() {
    $cache = \Drupal::cache('mandrill');
    $cached_urls = $cache->get('urls');

    if (!empty($cached_urls)) {
      return $cached_urls->data;
    }

    $data = array();
    $urls = $this->mandrill_api->getURLs();

    foreach ($urls as $url) {
      if (isset($url['url'])) {
        $data[$url['url']] = $url;
        $data[$url['url']]['time_series'] = $this->mandrill_api->getURLTimeSeries($url['url']);
      }
    }

    $cache->set('urls', $data);

    return $data;
  }

}
