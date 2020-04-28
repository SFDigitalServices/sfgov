<?php

namespace Drupal\mandrill;

/**
 * Interface for the Mandrill API.
 */
interface MandrillAPIInterface {
  public function isLibraryInstalled();
  public function getMessages($email);
  public function getTemplates();
  public function getSubAccounts();
  public function getWebhooks();
  public function getInboundDomains();
  public function getInboundRoutes();
  public function getUser();
  public function getTags();
  public function getTag($tag);
  public function getTagTimeSeries($tag);
  public function getTagsAllTimeSeries();
  public function getSenders();
  public function getSender($email);
  public function getSenderTimeSeries($email);
  public function getURLs();
  public function getURLTimeSeries($url);
  public function addInboundDomain($domain);
  public function addWebhook($path, $events, $description = 'Drupal Webhook');
  public function deleteInboundDomain($domain);
  public function addInboundRoute($domain, $pattern, $url);
  public function sendTemplate($message, $template_id, $template_content);
  public function send(array $message);
}
