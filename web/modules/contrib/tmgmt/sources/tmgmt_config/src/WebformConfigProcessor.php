<?php

namespace Drupal\tmgmt_config;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Config\Schema\Sequence;

class WebformConfigProcessor extends DefaultConfigProcessor {

  /**
   * @var \Drupal\config_translation\ConfigEntityMapper
   */
  protected $configMapper;

  /**
   * {@inheritdoc}
   */
  public function extractTranslatables($schema, $config_data, $base_key = '') {
    $data = array();
    foreach ($schema as $key => $element) {

      if ($key == 'elements' && empty($base_key)) {
        $webform = $this->configMapper->getEntity();

        $translation_manager = \Drupal::service('webform.translation_manager');
        $source_elements = $translation_manager->getSourceElements($webform);

        $data[$key] = $this->convertElementsToData($source_elements);
        $data[$key]['#label'] = t('Elements');
        continue;
      }

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
        $data[$key] = array(
          '#label' => $definition['label'],
          '#text' => $config_data[$key],
          '#translate' => $this->isTranslatable($config_data[$key]),
        );
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function convertToTranslation($data) {
    $translation = parent::convertToTranslation($data);
    if (isset($translation['elements'])) {
      $translation['elements'] = Yaml::encode($this->convertTranslationToElements($translation['elements']));
    }
    return $translation;
  }

  /**
   * Converts the webform elements structure to translatable data.
   *
   * @param array $source_elements
   *   A nested webform elements structure.
   *
   * @return array
   *   The converted data structure.
   */
  protected function convertElementsToData(array $source_elements) {
    $data = [];
    foreach ($source_elements as $key => $value) {
      $safe_key = str_replace('#', 'pound_', $key);
      if (is_array($value)) {
        $data[$safe_key] = $this->convertElementsToData($value);
        $data[$safe_key]['#label'] = $key;
      }
      else {
        $data[$safe_key] = [
          // @todo Is there a better label?
          '#label' => $key,
          '#text' => $value,
          '#translate' => $this->isTranslatable($value),
        ];
      }
    }
    return $data;
  }

  /**
   * Converts the special elements data structure back to elements.
   *
   * @param array $translation
   *   The already converted data structure.
   *
   * @return array
   *   The webform elements structure with specially escaped characters replaced.
   */
  protected function convertTranslationToElements(array $translation) {
    $elements = [];
    foreach ($translation as $key => $value) {
      $safe_key = str_replace('pound_', '#', $key);
      if (is_array($value)) {
        $elements[$safe_key] = $this->convertTranslationToElements($value);
      }
      else {
        $elements[$safe_key] = $value;
      }
    }
    return $elements;
  }

  /**
   * Returns whether the value should be treated as translatable.
   *
   * If the value only consists of a token then treat it as untranslatable.
   *
   * @param string $value
   *   The configuration value.
   *
   * @return bool
   *   TRUE if it is translatable, FALSE otherwise.
   */
  protected function isTranslatable($value) {
    $translatable = TRUE;
    if (preg_match('/^\[[a-z-_:]+\]$/', $value)) {
      $translatable = FALSE;
    }
    return $translatable;
  }

}
