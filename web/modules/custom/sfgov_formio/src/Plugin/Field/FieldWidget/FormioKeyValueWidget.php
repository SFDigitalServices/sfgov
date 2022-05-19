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
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    foreach ($elements as $key => $element) {
      if (is_int($key)) {
        // Disable the weight value so that elements can't be moved.
        $elements[$key]['_weight']['#disabled'] = TRUE;

        // Alter the element's title and description field.
        $label = $element['description']['#default_value'];
        $elements[$key]['key']['#title'] = $this->t('@label (#@number)', ['@label' => $label, '@number' => $key + 1]);

        // Description field just holds the label, hide it.
        $elements[$key]['description']['#access'] = FALSE;

        // Remove the remove button from each element.
        unset($elements[$key]['actions']['remove_button']);
      }
    }

    // Hide the "add more" button. Removing it causes issues with saving values.
    $elements['add_more']['#access'] = FALSE;
    return $elements;
  }

}
