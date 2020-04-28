<?php

namespace Drupal\telephone_validation;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Locale\CountryManagerInterface;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\ShortNumberInfo;

/**
 * Performs telephone validation.
 */
class Validator {

  /**
   * Phone Number util.
   *
   * @var \libphonenumber\PhoneNumberUtil
   */
  public $phoneUtils;

  /**
   * Short Number info.
   *
   * @var \libphonenumber\ShortNumberInfo
   */
  public $shortNumberInfo;

  /**
   * Country Manager service.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  public $countryManager;

  /**
   * Validator constructor.
   */
  public function __construct(CountryManagerInterface $country_manager) {
    $this->phoneUtils = PhoneNumberUtil::getInstance();
    $this->shortNumberInfo = ShortNumberInfo::getInstance();
    $this->countryManager = $country_manager;
  }

  /**
   * Check if number is valid for given settings.
   *
   * @param string $value
   *   Phone number.
   * @param int $format
   *   Supported input format.
   * @param array $country
   *   (optional) List of supported countries. If empty all countries are valid.
   * @param bool $allow_emergency
   *   (optional) Whether or not to allow emergency numbers.
   * @param bool $allow_short
   *   (optional) Whether or not to allow short codes.
   *
   * @return bool
   *   Boolean representation of validation result.
   */
  public function isValid($value, $format, array $country = [], $allow_emergency = FALSE, $allow_short = FALSE) {

    try {
      // Get default country.
      $default_region = ($format == PhoneNumberFormat::NATIONAL) ? reset($country) : NULL;
      // Parse to object.
      $number = $this->phoneUtils->parse($value, $default_region);
    }
    catch (\Exception $e) {
      // If number could not be parsed by phone utils that's a one good reason
      // to say it's not valid.
      return FALSE;
    }
    $is_emergency = $this->shortNumberInfo->isEmergencyNumber($value, reset($country));
    $is_possible_short = $this->shortNumberInfo->isPossibleShortNumberForRegion($number, reset($country));

    // Test if allowed as an emergency number.
    $validation = ($allow_emergency && $is_emergency)
      ? AccessResult::allowed()
      : ((!$allow_emergency && $is_emergency)
        ? AccessResult::forbidden()
        : AccessResult::neutral()
      );
    // Test if allowed as a short code.
    $validation = ($allow_short && $is_possible_short && !$validation->isForbidden())
      ? AccessResult::allowed()
      : $validation;
    // Finally test to check if the number is valid.
    $validation = !$validation->isForbidden() && $this->phoneUtils->isValidNumber($number)
      ? AccessResult::allowed()
      : $validation;

    // Fail now if the number is not allowed.
    if (!$validation->isAllowed()) {
      return FALSE;
    }

    // If country array is not empty and default region can be loaded
    // do region matching validation.
    // This condition is always TRUE for national phone number format.
    if (!empty($country) && $default_region = $this->phoneUtils->getRegionCodeForNumber($number)) {
      // Check if number's region matches list of supported countries.
      if (array_search($default_region, $country) === FALSE) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Get list of countries with country code and leading digits.
   *
   * @return array
   *   Flatten array you can use it directly in select lists.
   */
  public function getCountryList() {
    $regions = [];
    foreach ($this->countryManager->getList() as $region => $name) {
      $region_meta = $this->phoneUtils->getMetadataForRegion($region);
      if (is_object($region_meta)) {
        $regions[$region] = (string) new FormattableMarkup('@country - @country_code', [
          '@country' => $name,
          '@country_code' => $region_meta->getCountryCode() . $region_meta->getLeadingDigits(),
        ]);
      }
    }
    return $regions;
  }

}
