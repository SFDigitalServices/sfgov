<?php

namespace Drupal\sfgov_formio\Plugin\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\tmgmt_content\DefaultFieldProcessor;

/**
 * Field processor for the Formio Json Content text field.
 */
class FormioFieldProcessor extends DefaultFieldProcessor {

  /**
   * {@inheritdoc}
   */
  public function extractTranslatableData(FieldItemListInterface $field) {
    // This logic just removes the key from tmgmt so that it doesn't get
    // translated and mess up the front-end.
    $data = [];
    if ($field->getName() === 'field_form_strings') {
      $field_definition = $field->getFieldDefinition();
      $data['#label'] = $field_definition->getLabel();
      foreach ($field->getValue() as $key => $value) {
        $entry = [
          '#translate' => TRUE,
          '#text' => $value['value'],
          '#label' => $value['key'],
        ];
        $data[$key]['value'] = $entry;
      }
    }
    // If there is no formio data, fallback to the default behavior.
    return $data ?: DefaultFieldProcessor::extractTranslatableData($field);
  }

}
