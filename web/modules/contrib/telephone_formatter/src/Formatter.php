<?php

namespace Drupal\telephone_formatter;

use Drupal\Core\Language\LanguageManagerInterface;
use libphonenumber\PhoneNumberUtil;

/**
 * Formatter service.
 *
 * @package Drupal\telephone_formatter
 */
class Formatter implements FormatterInterface {

  /**
   * Phone utils from libphonenumber lib.
   *
   * @var \libphonenumber\PhoneNumberUtil
   */
  protected $phoneUtils;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Validator constructor.
   */
  public function __construct(LanguageManagerInterface $languageManager) {
    $this->languageManager = $languageManager;
    $this->phoneUtils = PhoneNumberUtil::getInstance();
  }

  /**
   * {@inheritdoc}
   */
  public function format($input, $format, $region = NULL) {

    // Parse to object.
    $number = $this->phoneUtils->parse($input, $region);

    // Ensure number is valid.
    if (!$this->phoneUtils->isValidNumber($number)) {
      throw new \InvalidArgumentException('Number is invalid.');
    }

    // Format phone number.
    $value = $this->phoneUtils->format($number, $format);

    return $value;
  }

}
