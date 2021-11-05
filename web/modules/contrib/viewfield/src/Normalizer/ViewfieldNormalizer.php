<?php

namespace Drupal\viewfield\Normalizer;

use Drupal\hal\Normalizer\EntityReferenceItemNormalizer;

/**
 * A normalizer to handle Viewfiled fields.
 */
class ViewfieldNormalizer extends EntityReferenceItemNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\viewfield\Plugin\Field\FieldType\ViewfieldItem';

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    $value = parent::constructValue($data, $context);
    if ($value) {
      $value['display_id'] = $data['display_id'];
      $value['arguments'] = $data['arguments'];
      $value['items_to_display'] = $data['items_to_display'];
    }
    return $value;
  }

}
