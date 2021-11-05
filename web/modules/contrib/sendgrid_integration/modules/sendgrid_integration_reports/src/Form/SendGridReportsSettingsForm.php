<?php

namespace Drupal\sendgrid_integration_reports\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SendGridReportsSettingsForm.
 *
 * @package Drupal\sendgrid_integration_reports\Form
 */
class SendGridReportsSettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sendgrid_integration_reports_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sendgrid_integration_reports.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sendgrid_integration_reports.settings')->get();

    $form['sendgrid_integration_reports'] = [
      '#type' => 'container',
    ];

    $form['sendgrid_integration_reports']['message'] = [
      '#markup' => t('Data and responses from Sendgrid are cached for performance reasons. If you make changes to these settings charts cache will be dropped automatically.'),
    ];

    $current_date = date_format(new \Datetime(), 'Y-m-d');
    $form['sendgrid_integration_reports']['start_date'] = [
      '#type' => 'date',
      '#title' => t('Global Stats Start Date'),
      '#default_value' => !empty($config['start_date']) ? $config['start_date'] : date_format(new \Datetime('-30 days'), 'Y-m-d'),
      '#attributes' => ['type' => 'date', 'max' => $current_date],
      '#required' => FALSE,
      '#description' => t('Start date in the format of mm/dd/YYYY. Defaults to 30 days back.'),
    ];

    $form['sendgrid_integration_reports']['end_date'] = [
      '#type' => 'date',
      '#title' => t('Global Stats End Date'),
      '#default_value' => !empty($config['end_date']) ? $config['end_date'] : $current_date,
      '#attributes' => ['type' => 'date', 'max' => $current_date],
      '#required' => FALSE,
      '#description' => t('End date in the format of mm/dd/YYYY. Defaults to today.'),
    ];

    $options = ['day', 'week', 'month'];
    $form['sendgrid_integration_reports']['aggregated_by'] = [
      '#type' => 'select',
      '#title' => t('Global Stats Aggregation'),
      '#default_value' => !empty($config['aggregated_by']) ? $config['aggregated_by'] : 0,
      '#required' => FALSE,
      '#description' => t('Aggregation of data. Defaults to day.'),
      '#options' => array_combine($options, $options),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (strtotime($values['start_date']) > strtotime($values['end_date'])) {
      $form_state->setError(
        $form['sendgrid_integration_reports']['start_date'],
        t('The %start could not be later than %end.', [
          '%start' => $form['sendgrid_integration_reports']['start_date']['#title'],
          '%end' => $form['sendgrid_integration_reports']['end_date']['#title'],
        ])
      );
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('sendgrid_integration_reports.settings');
    $values = $form_state->getValues();
    $elems = [
      'start_date',
      'end_date',
      'aggregated_by',
    ];
    foreach ($elems as $elem) {
      $config->set($elem, $values[$elem]);
    }
    $config->save();
    parent::submitForm($form, $form_state);
    // Clear the cache since the settings have been changed.
    \Drupal::cache('sendgrid_integration_reports')->deleteAll();
  }

}
