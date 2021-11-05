<?php

namespace Drupal\Tests\telephone_validation\Kernel;

use Drupal\field\Entity\FieldConfig;
use \Drupal\Tests\telephone\Kernel\TelephoneItemTest as BaseItemTest;

/**
 * @group telephone
 */
class ConfigTest extends BaseItemTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['telephone_validation'];

  /**
   * Tests if global schema is valid.
   */
  public function testGlobalSchema() {
    $config = $this->config('telephone_validation.settings');
    $config->set('format', 1);
    $config->set('country', []);
    $config->save();
  }

  /**
   * Tests if third party settings schema is valid.
   */
  public function testThirdPartySettingsSchema() {
    $config = FieldConfig::loadByName('entity_test', 'entity_test', 'field_test');
    $config->setThirdPartySetting('telephone_validation', 'format', 0);
    $config->setThirdPartySetting('telephone_validation', 'country', []);
    $config->save();
  }

}
