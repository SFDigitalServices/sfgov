<?php

namespace Drupal\sfgov_formio\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key_value_field\Plugin\Field\FieldWidget\KeyValueTextareaWidget;
use Drupal\Core\Field\WidgetBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Defines the 'formio_key_value_widget' field widget.
 *
 * @FieldWidget(
 *   id = "formio_key_value_widget",
 *   label = @Translation("Formio Key/Value"),
 *   field_types = {
 *     "key_value_long",
 *     "formio_key_value_item",
 *   },
 * )
 */
class FormioKeyValueWidget extends KeyValueTextareaWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['key']['#disabled'] = TRUE;
    $element['#allowed_formats'] = ['plain_text'];

    // Prevent Editors from changing English values.
    if ($form_state->getformObject()->getEntity()->language()->getId() === 'en') {
      $element['#disabled'] = TRUE;
    }

    // These elements need to exist to prevent a "This value should not be null"
    // validation error.
    $label = [
      'label' => [
      '#title' => 'Label',
      '#type' => 'textfield',
      '#access' => FALSE,
      '#default_value' => isset($items[$delta]->label) ? $items[$delta]->label : NULL,
      '#maxlength' => 255,
      ],
    ];
    $nested_location = [
      'nested_location' => [
      '#title' => 'Nested Location',
      '#type' => 'textfield',
      '#access' => FALSE,
      '#default_value' => isset($items[$delta]->nested_location) ? $items[$delta]->nested_location : NULL,
      '#maxlength' => 255,
      ],
    ];

    $element += $label;
    $element += $nested_location;

    // Alter the element's title and description field.
    $element["key"]["#title"] = $this->t('(#@number) @label', ['@number' => $delta + 1, '@label' => $items[$delta]->label]);
     return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    foreach ($elements as $key => $element) {
      if (is_int($key)) {
        // Remove the remove button from each element.
        unset($elements[$key]['actions']['remove_button']);
      }
    }

    // Only show the clear button if there are values in the form.
    $display_clear = FALSE;
    foreach ($elements as $key => $val) {
      if (is_int($key)) {
       $display_clear = TRUE;
       break;
      }
    }
    if ($display_clear) {
      // Stealing and altering the functionality of the add more button because
      // a custom button doesn't play nicely with WidgetBase.php.
      $elements['add_more'] = [
      '#type' => 'submit',
      '#weight' => 100,
      '#value' => t('Clear All Strings'),
      '#submit' => [[static::class, 'clear_formio_fields_submit']],
      '#ajax' => [
        'callback' => [static::class, 'clear_formio_fields_ajax'],
        'wrapper' => 'field-form-id-0-subform-field-form-strings-add-more-wrapper',
        ],
      ];
    }
    else {
      // Hide the add more button regardless.
      $elements['add_more']['#access'] = FALSE;
    }

    return $elements;
  }

  /**
   * Submit function for removing formio fields.
   *
   * @param object $form
   *   Array element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  function clear_formio_fields_submit(&$form, FormStateInterface $form_state) {
    // Find the correct widget value to alter (Logic adapted from submitRemove
    // in widgetbase).
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];
    $field_state = WidgetBase::getWidgetState($parents, $field_name, $form_state);
    $field_state['items_count'] = 0;

    static::setWidgetState($parents, $field_name, $form_state, $field_state);
    $form_state->setRebuild();
  }

  /**
   * Ajax function for removing formio fields.
   *
   * @param object $form
   *   Array element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  function clear_formio_fields_ajax(&$form, FormStateInterface $form_state) {
    // Find the correct widget value to alter (Logic adapted from
    // removeAjaxContentRefresh in widgetbase).
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    foreach ($element as $key => $value) {
      if (is_int($key)) {
        unset($element[$key]);
      }
    }
    return $element;
  }


}
