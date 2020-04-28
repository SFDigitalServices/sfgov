<?php

namespace Drupal\Tests\telephone_validation\Unit;

use Drupal\telephone_validation\Validator;
use Drupal\Tests\UnitTestCase;
use libphonenumber\PhoneNumberFormat;

/**
 * @coversDefaultClass  Drupal\telephone_validation\Validator
 * @group Telephone
 */
class ValidatorTest extends UnitTestCase {

  /**
   * Country manager service mock.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $mock = $this->getMock('Drupal\Core\Locale\CountryManagerInterface');
    $mock->expects($this->any())
      ->method('getList')
      ->withAnyParameters()
      ->willReturn(['NO' => 'Norway', 'CA' => 'Canada']);
    $this->countryManager = $mock;
  }

  /**
   * Tests get country list.
   *
   * ::covers getCountryList.
   */
  public function testCountryList() {
    $validator = new Validator($this->countryManager);
    $list = $validator->getCountryList();
    $this->assertEquals('Norway - 47', $list['NO']);
  }

  /**
   * Tests phone number validation.
   *
   * ::covers isValid.
   */
  public function testIsValid() {
    // Test valid Canadian number.
    $number = '2507638884';

    // Instantiate validator.
    $validator = new Validator($this->countryManager);

    // Test if number passes if format is National and number matches allowed
    // country.
    $this->assertTrue($validator->isValid($number, PhoneNumberFormat::NATIONAL, ['CA']));

    // Test if number fails if country is not supported.
    $this->assertFalse($validator->isValid($number, PhoneNumberFormat::NATIONAL, ['XYZ']));

    // Test if number fails if country doesn't match.
    $this->assertFalse($validator->isValid($number, PhoneNumberFormat::NATIONAL, ['NO']));

    // Test if number fails if format is wrong.
    $this->assertFalse($validator->isValid($number, PhoneNumberFormat::INTERNATIONAL, ['CA']));
    $this->assertFalse($validator->isValid($number, PhoneNumberFormat::E164, ['CA']));

    // Test if number passes if we add country code.
    $this->assertTrue($validator->isValid('+1' . $number, PhoneNumberFormat::E164, ['CA']));

    // Test if number passes if country is not defined.
    $this->assertTrue($validator->isValid('+1' . $number, PhoneNumberFormat::E164, []));

    // Test if number fails if it's prefix doesn't belong to one of the
    // countries from white-list.
    $this->assertFalse($validator->isValid('+1' . $number, PhoneNumberFormat::E164, ['NO']));

    // Test emergency number.
    $this->assertFalse($validator->isValid('911', PhoneNumberFormat::NATIONAL, ['US']));
    $this->assertFalse($validator->isValid('911', PhoneNumberFormat::NATIONAL, ['US']), FALSE, TRUE);
    $this->assertTrue($validator->isValid('911', PhoneNumberFormat::NATIONAL, ['US'], TRUE));
    $this->assertTrue($validator->isValid('911', PhoneNumberFormat::NATIONAL, ['US'], TRUE, TRUE));
    // Test short code.
    $this->assertFalse($validator->isValid('311', PhoneNumberFormat::NATIONAL, ['US']));
    $this->assertFalse($validator->isValid('311', PhoneNumberFormat::NATIONAL, ['US']), TRUE);
    $this->assertTrue($validator->isValid('311', PhoneNumberFormat::NATIONAL, ['US'], FALSE, TRUE));
    $this->assertTrue($validator->isValid('311', PhoneNumberFormat::NATIONAL, ['US'], TRUE, TRUE));
  }

}
