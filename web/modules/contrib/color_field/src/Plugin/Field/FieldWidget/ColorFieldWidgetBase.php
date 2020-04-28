<?php

namespace Drupal\color_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for color_field widgets.
 */
abstract class ColorFieldWidgetBase extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#uid'] = Html::getUniqueId('color-field-' . $this->fieldDefinition->getName());

    // Prepare color.
    $color = NULL;
    if (isset($items[$delta]->color)) {
      $color = $items[$delta]->color;
      if (substr($color, 0, 1) !== '#') {
        $color = '#' . $color;
      }
    }

    $input = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 7,
      '#required' => $element['#required'],
      '#default_value' => $color,
    ];

    if ($this->getFieldSetting('opacity')) {
      $element['color'] = $input;
      $element['color']['#title'] = $this->t('Color');
      $element['color']['#error_no_message'] = TRUE;
      $element['#type'] = 'fieldset';

      $element['opacity'] = [
        '#title' => $this->t('Opacity'),
        '#type' => 'number',
        '#min' => 0,
        '#max' => 1,
        '#step' => 0.01,
        '#required' => $element['#required'],
        '#default_value' => isset($items[$delta]->opacity) ? $items[$delta]->opacity : NULL,
        '#placeholder' => $this->getSetting('placeholder_opacity'),
        '#error_no_message' => TRUE,
      ];
    }
    else {
      $element['color'] = $element + $input;
      $element['#type'] = 'fieldset';
    }

    return $element;
  }

}
