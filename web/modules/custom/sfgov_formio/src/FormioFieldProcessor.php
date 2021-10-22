<?php

namespace Drupal\sfgov_formio;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\tmgmt_content\MetatagsFieldProcessor;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Field processor for the metatags field.
 */
class FormioFieldProcessor extends MetatagsFieldProcessor {

  /**
   * {@inheritdoc}
   */
  public function extractTranslatableData(FieldItemListInterface $field) {
    $data = [];
    if ($field->getName() === 'field_form_id') {
      $formio_paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($field->target_id);
      // The field_form_id field sometimes contains paragraphs with a formio
      // reference field. If that field exists, make a call to the referenced
      // formio doc and set each field within as a new field in the translation.
      if ($formio_paragraph->hasfield('field_formio_data_source')) {
        $formio_data_source = $formio_paragraph->get('field_formio_data_source')->value;

        // If the results aren't in cache, set the cache.
        $cid = 'formio_data:nid:' . $formio_paragraph->get('parent_id')->value;
        if (!\Drupal::cache()->get($cid)) {
          if ($formio_data = $this->getFormioData($formio_data_source)) {
            \Drupal::cache()->set($cid, $formio_data);
          }
        }

        if (\Drupal::cache()->get($cid)) {
          foreach (\Drupal::cache()->get($cid)->data as $label => $value ) {
            $label_values = explode ('.', $label);
              $entry = [
                'description' => [
                  '#translate' => TRUE,
                  '#text' => $value,
                  '#label' => $label_values[0],
                ],
              ];
            $data[$label] = $entry;
          }
        }
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setTranslations($field_data, FieldItemListInterface $field) {
    return parent::setTranslations($field_data, $field);
  }

  /**
   * Gets the formio field labels and values.
   *
   * @param string $formio_data_source
   *   The url being used for the formio call.
   *
   * @return array
   *   The array of json field labels and data
   */
  protected function getFormioData($formio_data_source) {
    $data = [];
    // Do everything we can to ensure its a valid url source.
    if (UrlHelper::isExternal($formio_data_source)) {
      $formio_config = \Drupal::config('sfgov_formio.settings');
      $formio_url = trim($formio_config->get('formio_base_url') . $formio_data_source);
      if (UrlHelper::isValid($formio_url)) {
        $data = (array) json_decode(file_get_contents($formio_url));
      }
    }
    return $data;
  }

}
