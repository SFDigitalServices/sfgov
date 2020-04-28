<?php

namespace Drupal\tmgmt_language_combination\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'tmgmt_language_combination_default' widget.
 *
 * @FieldWidget(
 *   id = "tmgmt_language_combination_default",
 *   label = @Translation("Select list"),
 *   description = @Translation("Default widget for allowing users to define translation combination."),
 *   field_types = {
 *     "tmgmt_language_combination"
 *   }
 * )
 */
class LanguageCombinationWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if ($form_state->get('list_all_languages')) {
      $languages_options = tmgmt_language_combination_languages_predefined_list();
    }
    else {
      $languages_options = array();
      foreach (\Drupal::languageManager()->getLanguages() as $code => $language) {
        $languages_options[$code] = $language->getName();
      }
    }

    $options = array('_none' => t('- None -')) + $languages_options;

    $element['language_from'] = array(
      '#type' => 'select',
      '#title' => t('From'),
      '#options' => $options,
      '#default_value' => isset($items[$delta]) ? $items[$delta]->language_from : '',
      '#attributes' => array('class' => array('from-language')),
    );

    $element['language_to'] = array(
      '#type' => 'select',
      '#title' => t('To'),
      '#options' => $options,
      '#default_value' => isset($items[$delta]) ? $items[$delta]->language_to : '',
      '#attributes' => array('class' => array('to-language')),
    );

    return $element;
  }

}
