<?php

namespace Drupal\sfgov_alerts;

use Drupal\Core\Entity\Entity;

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
   * @param \Drupal\Core\Entity\Entity $entity
   */
  public function __construct(Entity $entity) {
    $this->type = $entity->label();
    $this->text = strip_tags($entity->field_alert_text->value);
    $this->expiration_original = $entity->original->field_alert_expiration_date->value;
    $this->expiration_updated = $entity->field_alert_expiration_date->value;
  }

  /**
   * Send alert notification to dblog and screen.
   */
  public function notify() {

    if ($this->expiration_original != $this->expiration_updated) {

      $message = t(
        '<em>@type</em> Alert Expiration Date has changed from <b>@expiration_original</b> to <b>@expiration.</b><br> Alert Text: <b>@text</b>', [
        '@type' => $this->type,
        '@text' => $this->text,
        '@expiration' => $this->expiration_updated,
        '@expiration_original' => $this->expiration_original,
      ]);

      \Drupal::logger('sfgov_alerts')->info($message);
      \Drupal::messenger()->addMessage($message);
    }
  }
}
