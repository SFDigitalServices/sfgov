<?php

namespace Drupal\google_analytics_reports\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Number;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Provides base filter functionality for Google Analytics fields.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("google_analytics_base")
 */
class GoogleAnalyticsBase extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $alwaysMultiple = TRUE;

  /**
   * Stores wheather this is a custom field or not.
   *
   * @var bool
   */
  public $isCustom = NULL;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->isCustom  = google_analytics_reports_is_custom($this->realField);
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    if ($this->isCustom) {
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
  public function operatorOptions($which = 'title') {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if ($this->isCustom) {
      $this->realField = google_analytics_reports_custom_to_variable_field($this->realField, $this->options['custom_field_number']);
    }
    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($this->realField);
    }
  }

  /**
   * Provide list of operators.
   */
  public function operators() {
    return [];
  }

}
