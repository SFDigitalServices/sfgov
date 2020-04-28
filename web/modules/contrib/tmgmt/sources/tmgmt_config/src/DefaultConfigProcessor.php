<?php

namespace Drupal\tmgmt_config;

use Drupal\config_translation\ConfigMapperInterface;
use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Config\Schema\Sequence;
use Drupal\Core\Render\Element;

/**
 * Default implementation of the config processor.
 */
class DefaultConfigProcessor implements ConfigProcessorInterface {

  /**
   * @var \Drupal\config_translation\ConfigMapperInterface
   */
  protected $configMapper;

  /**
   * {@inheritdoc}
   */
  public function setConfigMapper(ConfigMapperInterface $config_mapper) {
    $this->configMapper = $config_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public function extractTranslatables($schema, $config_data, $base_key = '') {
    $data = array();
    foreach ($schema as $key => $element) {
      $element_key = isset($base_key) ? "$base_key.$key" : $key;
      $definition = $element->getDataDefinition();
      // + array('label' => t('N/A'));
      if ($element instanceof Mapping || $element instanceof Sequence) {
        // Build sub-structure and include it with a wrapper in the form
        // if there are any translatable elements there.
        $sub_data = $this->extractTranslatables($element, $config_data[$key], $element_key);
        if ($sub_data) {
          $data[$key] = $sub_data;
          $data[$key]['#label'] = $definition->getLabel();
        }
      }
      else {
        if (!isset($definition['translatable']) || !isset($definition['type']) || empty($config_data[$key])) {
          continue;
        }
        $data[$key] = [
          '#label' => $definition['label'],
          '#text' => $config_data[$key],
          '#translate' => TRUE,
        ];
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function convertToTranslation($data) {
    $children = Element::children($data);
    if ($children) {
      $translation = [];
      foreach ($children as $name) {
        $property_data = $data[$name];
        $translation[$name] = $this->convertToTranslation($property_data);
      }
      return $translation;
    }
    elseif (isset($data['#translation']['#text'])) {
      return $data['#translation']['#text'];
    }
  }
}
