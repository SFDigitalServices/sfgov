<?php

namespace Drupal\sfgov_vaccine\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vaccine sites page filter.
 */
class FilterSitesForm extends FormBase {

  /**
   * The configuration factory.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Get config.
   */
  private function settings($value) {
    return $this->configFactory->get('sfgov_vaccine.settings')->get($value);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vaccine_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'notranslate';

    $form['container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t($this->settings('form_strings.title')),
      '#attributes' => [
        'data-filter-toggle-container' => TRUE,
      ],
    ];
    $form['container']['toggle'] = [
      '#type' => 'container',
      '#attributes' => [
        'data-filter-toggle-content' => TRUE,
      ],
    ];
    $form['container']['toggle']['items'] = [
      '#type' => 'container',
    ];
    $form['container']['toggle']['items']['single_checkboxes'] = [
      '#type' => 'container',
    ];
    $form['container']['toggle']['items']['single_checkboxes']['restrictions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t($this->settings('form_strings.restrictions')),
      '#default_value' => TRUE,
    ];

    $form['container']['toggle']['items']['single_checkboxes']['available'] = [
      '#type' => 'checkbox',
      '#title' => $this->t($this->settings('form_strings.available')),
      '#default_value' => FALSE,
    ];
    $form['container']['toggle']['items']['single_checkboxes']['wheelchair'] = [
      '#type' => 'checkbox',
      '#title' => $this->t($this->settings('access_mode.wheelchair.text')),
      '#default_value' => FALSE,
    ];

    $settings_languages = $this->settings('languages');
    $options_languages = [];
    foreach ($settings_languages as $key => $value) {
      $options_languages[$key] = $this->t($value);
    }

    $form['container']['toggle']['items']['language'] = [
      '#type' => 'select',
      '#title' => $this->t($this->settings('form_strings.language_label')),
      '#title_display' => 'invisible',
      '#options' => $options_languages,
      '#default_value' => 'any',
      '#multiple' => FALSE,
    ];

    $settings_access_mode = $this->settings('access_mode');
    $options_access_mode = [];
    foreach ($settings_access_mode as $key => $value) {
      if ($key != 'wheelchair') {
        $short_key = $value['short_key'];
        $text = $value['text'];
        $options_access_mode[$short_key] = $this->t($text);
      }
    }

    $form['container']['toggle']['items']['access_mode'] = [
      '#type' => 'select',
      '#title' => $this->t($this->settings('form_strings.access_mode_label')),
      '#title_display' => 'invisible',
      '#options' => $options_access_mode,
      '#default_value' => 'all',
      '#multiple' => FALSE,
    ];

    $settings_eligibility = $this->settings('eligibility');
    $options_eligibility = [];
    foreach ($settings_eligibility as $key => $value) {
      if ($key != 'second_dose_only') {
        $short_key = $value['short_key'];
        $text = $value['text'];
        $options_eligibility[$short_key] = $this->t($text);
      }
    }
    $form['container']['toggle']['items']['eligibility'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t($this->settings('form_strings.eligibility_label')),
      '#options' => $options_eligibility,
    ];

    $form['container']['toggle']['items']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t($this->settings('form_strings.submit_label')),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return NULL;
  }

}
