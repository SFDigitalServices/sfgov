<?php

namespace Drupal\sfgov_formio\Plugin\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\tmgmt_content\DefaultFieldProcessor;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;

/**
 * Field processor for the Formio Json Content text field.
 */
class FormioFieldProcessor extends DefaultFieldProcessor {

  /**
   * {@inheritdoc}
   */
  public function extractTranslatableData(FieldItemListInterface $field) {
    $data = [];
    if ($field->getName() === 'field_formio_json_content') {
      $formio_data = $this->setFormioData($field);
      if (!empty($formio_data)) {
        $data['#label'] = 'Formio Data';
        foreach ($formio_data as $label => $value ) {
          $label = str_replace('.', '_', $label);
            $entry = [
              '#translate' => TRUE,
              '#text' => $value,
              '#label' => $label,
            ];
          $data[$label] = $entry;
        }
        $data['#multivalue_entry'] = TRUE;
      }
    }
    // If there is no formio data, fallback to the default behavior.
    return $data ?: DefaultFieldProcessor::extractTranslatableData($field);
  }

  /**
   * {@inheritdoc}
   */
  public function setTranslations($field_data, FieldItemListInterface $field) {
    if (isset($field_data['#multivalue_entry'])) {
      // Pull the data into an array and encode it.
      $data = [];
      $langcode = $field->getLangcode();
      foreach($field_data as $label => $translation) {
        if (isset($translation['#translation'])) {
          $label = str_replace('_', '.', $label);
          $data[$langcode][$label] = $translation['#translation']['#text'];
        }
      }
      $field->value = json_encode($data);
    }
    else {
      // Use the default setTranslation.
      return parent::setTranslations($field_data, $field);
    }
    return;
  }

  /**
   * Cache and return the formio field labels and values.
   *
   * @param Drupal\Core\Field\FieldItemListInterface $field
   *   The field being translated
   *
   * @return array
   *   The array of json data from formio. Can return an empty array.
   */
  protected function setFormioData(FieldItemListInterface $field) {
    $parent = $field->getParent()->getEntity();
    $node_tags = $parent->getCacheTags();
    $cid = 'formio_data:' . $node_tags[0];
    $data = [];
    if (\Drupal::cache()->get($cid)) {
      $data = \Drupal::cache()->get($cid)->data;
    }
    elseif ($parent->get('formio_url')->value) {
      $formio_url = $parent->get('formio_url')->value;
      $data = (array) json_decode(file_get_contents($formio_url));
      \Drupal::cache()->set($cid, $data, CACHE::PERMANENT, $node_tags);
    }
    return $data;
  }
}
