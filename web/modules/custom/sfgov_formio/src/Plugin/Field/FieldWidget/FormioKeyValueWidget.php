<?php

namespace Drupal\sfgov_formio\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key_value_field\Plugin\Field\FieldWidget\KeyValueTextareaWidget;

/**
 * Defines the 'formio_key_value' field widget.
 *
 * @FieldWidget(
 *   id = "formio_key_value",
 *   label = @Translation("Formio Key/Value"),
 *   field_types = {"key_value_long"},
 * )
 */
class FormioKeyValueWidget extends KeyValueTextareaWidget {

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    foreach ($elements as $key => $element) {
      if (is_int($key)) {
        // Add an easy way for editors to know each entry number
        $elements[$key]['number'] = [
          '#type' => 'label',
          '#title' => 'Entry: ' . ($key + 1),
          '#title_display' => 'above',
        ];
        // Remove the remove button from each element.
        unset($elements[$key]['actions']['remove_button']);
        // Remove the confusing "weight" value from each element.
        unset($elements[$key]['_weight']);
      }
    }

    // Hide the "add more" button. Removing it causes issues with saving values.
    $elements['add_more']['#access'] = FALSE;
    return $elements;
  }

}
