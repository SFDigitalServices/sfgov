<?php

namespace Drupal\tmgmt\Translator;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Used by translator to return the boolean result of a check with a reason.
 */
abstract class TranslatorResult {

  /**
   * TRUE or FALSE for response.
   *
   * @var bool
   */
  protected $success;

  /**
   * Message in case success is FALSE.
   *
   * @var string
   */
  protected $message;

  /**
   * Constructs a result object.
   *
   * @param bool $success
   *   Whether or not the check was successful.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $reason
   *   The reason in case of an unsuccessful check.
   */
  protected function __construct($success, TranslatableMarkup $reason = NULL) {
    $this->success = $success;
    $this->message = $reason;
  }

  /**
   * Returns the reason for an unsuccessful result.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The reason.
   */
  public function getReason() {
    return $this->message;
  }

  /**
   * Returns the object state on success.
   */
  public function getSuccess() {
    return $this->success;
  }

  /**
   * Sets the value success to FALSE and sets the $message accordingly.
   *
   * @param string $message
   *   This is the value to be saved as message for object.
   */
  protected function setNo($message) {
    $this->success = FALSE;
    $this->message = $message;
  }

  /**
   * Sets the value success to TRUE.
   */
  protected function setYes() {
    $this->success = TRUE;
  }

  /**
   * Returns the object with TRUE.
   *
   * @return static
   *   This returns the instance of the object with desired values.
   */
  public static function yes() {
    return new static(TRUE);
  }

  /**
   * Returns the object with FALSE and a message.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $reason
   *   The reason in case of an unsuccessful check.
   *
   * @return static
   *   This returns the instance of the object with desired values.
   */
  public static function no($reason) {
    return new static(FALSE, $reason);
  }
}
