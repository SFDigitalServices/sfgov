<?php

namespace Drupal\google_analytics_reports\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Number;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Provides base argument functionality for Google Analytics fields.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("google_analytics_argument")
 */
class GoogleAnalyticsArgument extends ArgumentPluginBase {

  protected $isCustom = NULL;

  public $operator;


  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->isCustom = google_analytics_reports_is_custom($this->realField);
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    if ($this->isCustom) {
      $options['default_argument_type']['default'] = 'google_analytics_path';
      $options['custom_field_number'] = ['default' => 1];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if ($this->isCustom) {
      $form['custom_field_number'] = [
        '#type' => 'textfield',
        '#title' => t('Custom field number'),
        '#default_value' => isset($this->options['custom_field_number']) ? $this->options['custom_field_number'] : 1,
        '#size' => 2,
        '#maxlength' => 2,
        '#required' => TRUE,
        '#element_validate' => [Number::class, 'validateNumber'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {

    if ($this->isCustom) {
      $this->realField = google_analytics_reports_custom_to_variable_field($this->realField, $this->options['custom_field_number']);
    }

    $this->operator = '==';
    $this->query->addWhere(1, $this->realField, $this->argument, $this->operator);
  }

}
