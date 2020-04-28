<?php

namespace Drupal\tmgmt_content;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Interface for content entity source field processors.
 *
 * @ingroup tmgmt_content
 */
interface FieldProcessorInterface {

  /**
   * Extracts the translatatable data structure from the given field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field object.
   *
   * @return array $data
   *   An array of elements where each element has the following keys:
   *   - #text
   *   - #translate
   *
   * @see \Drupal\tmgmt_content\Plugin\tmgmt\Source\ContentEntitySource::extractTranslatableData()
   */
  public function extractTranslatableData(FieldItemListInterface $field);

  /**
   * Process the translated data for this field back into a structure that can be saved by the content entity.
   *
   * @param array $field_data
   *   The translated data for this field.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field object.
   *
   * @see \Drupal\tmgmt_content\Plugin\tmgmt\Source\ContentEntitySource::doSaveTranslations()
   */
  public function setTranslations($field_data, FieldItemListInterface $field);

}
