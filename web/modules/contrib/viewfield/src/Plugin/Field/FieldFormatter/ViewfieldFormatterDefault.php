<?php

namespace Drupal\viewfield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\views\Views;

/**
 *
 * @FieldFormatter(
 *   id = "viewfield_default",
 *   label = @Translation("Viewfield"),
 *   field_types = {"viewfield"}
 * )
 */
class ViewfieldFormatterDefault extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_title' => 'hidden',
      'always_build_output' => 0,
      'empty_view_title' => 'hidden',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['view_title'] = [
      '#type' => 'select',
      '#title' => $this->t('View title'),
      '#options' => $this->getFieldLabelOptions(),
      '#default_value' => $this->getSetting('view_title'),
      '#description' => $this->t('Option to render the view display title.'),
    ];

    $form['always_build_output'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always build output'),
      '#default_value' => $this->getSetting('always_build_output'),
      '#description' => $this->t('Produce renderable output even if the view produces no results.<br>This option may be useful for some specialized cases, e.g., to force rendering of an attachment display even if there are no view results.'),
    ];

    $always_build_output_name = 'fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][always_build_output]';
    $form['empty_view_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Empty view title'),
      '#options' => $this->getFieldLabelOptions(),
      '#default_value' => $this->getSetting('empty_view_title'),
      '#description' => $this->t('Option to output the view display title even when the view produces no results.<br>This option has an effect only when <em>Always build output</em> is also selected.'),
      '#states' => ['visible' => [':input[name="' . $always_build_output_name . '"]' => ['checked' => TRUE]]],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $label_options = $this->getFieldLabelOptions();
    $summary = [];

    $summary[] = $this->t('Show view title: @view_title', [
      '@view_title' => $label_options[$settings['view_title']],
    ]);
    $summary[] = $this->t('Always build output: @always_build_output', [
      '@always_build_output' => $this->getCheckboxLabel($settings['always_build_output']),
    ]);
    if ($settings['always_build_output']) {
      $summary[] = $this->t('Show empty view title: @show_empty_view_title', [
        '@show_empty_view_title' => $label_options[$settings['empty_view_title']],
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    $elements['#theme'] = 'viewfield';
    $elements['#entity'] = $items->getEntity();
    $elements['#entity_type'] = $items->getEntity()->getEntityTypeId();
    $elements['#bundle'] = $items->getEntity()->bundle();
    $elements['#field_name'] = $this->fieldDefinition->getName();
    $elements['#field_type'] = $this->fieldDefinition->getType();
    $elements['#view_mode'] = $this->viewMode;

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();

    if ($this->getFieldSetting('force_default')) {
      $values = $this->fieldDefinition->getDefaultValue($entity);
    }
    else {
      $values = [];
      foreach ($items as $delta => $item) {
        $values[$delta] = $item->getValue();
      }
    }

    $elements = [];

    $always_build_output = $this->getSetting('always_build_output');
    $view_title = $this->getSetting('view_title');
    $empty_view_title = $this->getSetting('empty_view_title');

    foreach ($values as $delta => $value) {
      $target_id = $value['target_id'];
      $display_id = $value['display_id'];
      $arguments = $this->processArguments($value['arguments'], $entity);

      // @see views_embed_view()
      // @see views_get_view_result()
      $view = Views::getView($target_id);
      if (!$view || !$view->access($display_id)) {
        continue;
      }

      $view->setArguments($arguments);
      $view->setDisplay($display_id);
      $view->preExecute();
      $view->execute();

      $rendered_view = $view->buildRenderable($display_id, $arguments);
      if (!empty($view->result) || $always_build_output) {
        $elements[$delta] = [
          '#theme' => 'viewfield_item',
          '#content' => $rendered_view,
          '#title' => $view->getTitle(),
          '#label_display' => empty($view->result) ? $empty_view_title : $view_title,
          '#delta' => $delta,
          '#field_name' => $this->fieldDefinition->getName(),
          '#view_id' => $target_id,
          '#display_id' => $display_id,
        ];
        // Add arguments to view cache keys to allow multiple viewfields with
        // same view but different arguments per page.
        $cache_keys = array_merge($rendered_view['#cache']['keys'], $arguments);
        $elements[$delta]['#content']['#cache']['keys'] = $cache_keys;
      }
    }

    return $elements;
  }

  /**
   * Perform argument parsing and token replacement.
   *
   * @param string $argument_string
   *   The raw argument string.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity containing this field.
   *
   * @return array
   *   The array of processed arguments.
   */
  protected function processArguments($argument_string, $entity) {
    $arguments = [];

    if (!empty($argument_string)) {
      $pos = 0;
      while ($pos < strlen($argument_string)) {
        $found = FALSE;
        // If string starts with a quote, start after quote and get everything
        // before next quote.
        if (strpos($argument_string, '"', $pos) === $pos) {
          if (($quote = strpos($argument_string, '"', ++$pos)) !== FALSE) {
            // Skip pairs of quotes.
            while (!(($ql = strspn($argument_string, '"', $quote)) & 1)) {
              $quote = strpos($argument_string, '"', $quote + $ql);
            }
            $arguments[] = str_replace('""', '"', substr($argument_string, $pos, $quote + $ql - $pos - 1));
            $pos = $quote + $ql + 1;
            $found = TRUE;
          }
        }
        else {
          $arguments = explode('/', $argument_string);
          $pos = strlen($argument_string) + 1;
          $found = TRUE;
        }
        if (!$found) {
          $arguments[] = substr($argument_string, $pos);
          $pos = strlen($argument_string);
        }
      }

      $token_service = \Drupal::token();
      $token_data = [$entity->getEntityTypeId() => $entity];
      foreach ($arguments as $key => $value) {
        $arguments[$key] = $token_service->replace($value, $token_data);
      }
    }

    return $arguments;
  }

  /**
   * Get a printable label for a checkbox value.
   *
   * @param string $value
   *   The checkbox value.
   *
   * @return string
   *   The label for the checkbox value.
   */
  protected function getCheckboxLabel($value) {
    return !empty($value) ? $this->t('Yes') : $this->t('No');
  }

  /**
   * Returns an array of visibility options for field labels.
   *
   * @return array
   *   An array of visibility options.
   *
   * @see EntityViewDisplayEditForm::getFieldLabelOptions()
   */
  protected function getFieldLabelOptions() {
    return [
      'above' => $this->t('Above'),
      'inline' => $this->t('Inline'),
      'hidden' => '- ' . $this->t('Hidden') . ' -',
      'visually_hidden' => '- ' . $this->t('Visually Hidden') . ' -',
    ];
  }

}
