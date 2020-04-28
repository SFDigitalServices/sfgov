<?php

namespace Drupal\tmgmt_content;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Element;

/**
 * Field processor for the link field.
 */
class LinkFieldProcessor extends DefaultFieldProcessor {

  /**
   * {@inheritdoc}
   */
  public function extractTranslatableData(FieldItemListInterface $field) {
    $data = parent::extractTranslatableData($field);
    foreach (Element::children($data) as $key) {
      if (!empty($data[$key]['uri']['#translate'])) {
        $data[$key]['uri']['#translate'] = FALSE;
      }
    }

    return $data;
  }

}
