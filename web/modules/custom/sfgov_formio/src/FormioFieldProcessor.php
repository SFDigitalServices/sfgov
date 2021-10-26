<?php

namespace Drupal\sfgov_formio;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\tmgmt_content\DefaultFieldProcessor;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;

/**
 * Field processor for the metatags field.
 */
class FormioFieldProcessor extends DefaultFieldProcessor {

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
        $formio_data = $this->setFormioData($formio_paragraph);
        if (!empty($formio_data)) {
          foreach ($formio_data as $label => $value ) {
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
    // If there is no formio data, fallback to the default behavior.
    return $data ?: parent::extractTranslatableData($field);
  }

  /**
   * {@inheritdoc}
   */
  public function setTranslations($field_data, FieldItemListInterface $field) {
    return parent::setTranslations($field_data, $field);
  }

  /**
   * Cache and return the formio field labels and values.
   *
   * @param string $formio_data_source
   *   The url being used for the formio call.
   *
   * @return array
   *   The array of json data from formio. Can return an empty array.
   */
  protected function setFormioData($formio_paragraph) {
    $paragraph_tag = $formio_paragraph->getCacheTags()[0];
    $cid = 'formio_data:' . $paragraph_tag;
    $data = [];
    if (\Drupal::cache()->get($cid)) {
      $data = \Drupal::cache()->get($cid)->data;
    }
    else {
      $formio_data_source = $formio_paragraph->get('field_formio_data_source')->value;
      // Do everything we can to ensure its a valid url source.
      if (UrlHelper::isExternal($formio_data_source)) {
        $formio_config = \Drupal::config('sfgov_formio.settings');
        $formio_url = str_replace(
          '[form_url]',
          urlencode(trim($formio_data_source)),
          $formio_config->get('formio_translations_api_url'));
        if (UrlHelper::isValid($formio_url)) {
          $data = (array) json_decode(file_get_contents($formio_url));
          \Drupal::cache()->set($cid, $data, CACHE::PERMANENT, [$paragraph_tag]);
        }
      }
    }
    if (empty($data)) {
      \Drupal::logger('sfgov_formio')->notice('The url provided in field_formio_data_source on node @nid is not providing valid formio data for translations');
    }
    return $data;
  }

}
