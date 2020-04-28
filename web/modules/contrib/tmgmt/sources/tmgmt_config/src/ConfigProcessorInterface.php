<?php

namespace Drupal\tmgmt_config;

use Drupal\config_translation\ConfigMapperInterface;

interface ConfigProcessorInterface {


  public function setConfigMapper(ConfigMapperInterface $config_mapper);

  /**
   * @param \Drupal\Core\TypedData\TypedDataInterface[]|\Drupal\Core\Config\Schema\TypedConfigInterface $schema
   *   A list of schema definitions.
   * @param $config_data
   * @param string $base_key
   *
   * @return array
   */
  public function extractTranslatables($schema, $config_data, $base_key = '');

  /**
   * Converts a translated data structure.
   *
   * @param array $data
   *   The translated data structure.
   *
   * @return array
   *   Returns a translation array as expected by
   *   \Drupal\config_translation\FormElement\ElementInterface::setConfig().
   */
  public function convertToTranslation($data);
}
