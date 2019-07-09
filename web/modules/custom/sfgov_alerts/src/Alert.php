<?php

namespace Drupal\sfgov_alerts;

/**
 * Class Alert.
 */
class Alert {

  private $text;

  private $expiration_original;

  private $expiration_updated;

  private $type;

  /**
   * Alert constructor.
   * @param $type string
   * @param $text string
   * @param $expiration_original string
   * @param $expiration_updated string
   */
  public function __construct($type, $text, $expiration_original, $expiration_updated) {
    $this->type = $type;
    $this->text = strip_tags($text);
    $this->expiration_original = $expiration_original;
    $this->expiration_updated = $expiration_updated;
  }

  /**
   * Send alert notification to dblog and screen.
   */
  public function notify() {

    if ($this->expiration_original != $this->expiration_updated) {

      $log_message = t('@type - @expiration - @text', [
        '@type' => $this->type,
        '@text' => $this->text,
        '@expiration' => $this->expiration_updated
      ]);

      $screen_message = t(
        '@type Alert Expiration Date has changed from <b>@expiration_original</b> to <b>@expiration.</b><br> Alert Text: <b>@text</b>', [
        '@type' => $this->type,
        '@text' => $this->text,
        '@expiration' => $this->expiration_updated,
        '@expiration_original' => $this->expiration_original,
      ]);

      \Drupal::logger('sfgov_alerts')->info($log_message);
      \Drupal::messenger()->addMessage($screen_message);
    }
  }
}
