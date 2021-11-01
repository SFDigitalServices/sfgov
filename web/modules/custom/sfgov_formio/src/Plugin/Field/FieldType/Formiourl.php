<?php

namespace Drupal\sfgov_formio\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Component\Utility\UrlHelper;

class Formiourl extends FieldItemList implements FieldItemListInterface {

  use ComputedItemListTrait;

  /**
   * Compute the values.
   */
  protected function computeValue(){
    $parent_node = $this->getEntity();
    // Only use this field on form page content types
    if ($parent_node->bundle() === 'form_page') {
      // Return False if any step fails
      $formio_url = FALSE;
      if ($paragraph_nid = $parent_node->get('field_form_id')->target_id) {
        $form_paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($paragraph_nid);
        $formio_data_source = $form_paragraph->hasfield('field_formio_data_source') ? $form_paragraph->get('field_formio_data_source')->value : NULL;
        if (UrlHelper::isExternal($formio_data_source)) {
          // If its an external url, pull the existing config and use it to
          // generate a url.
          $formio_config = \Drupal::config('sfgov_formio.settings');
          $constructed_url = str_replace(
          '[form_url]',
          urlencode(trim($formio_data_source)),
          $formio_config->get('formio_translations_api_url'));
          $formio_url = UrlHelper::isValid($constructed_url) ? $constructed_url : FALSE;
        }
      }
      if (!$formio_url) {
        \Drupal::logger('sfgov_formio')->notice('The url provided in field_formio_data_source on node %nid is not providing valid formio data for translations', ['%nid' => $parent_node->id()]);
      }
      $this->list[0] = $this->createItem(0, $formio_url);
    }
  }

}
