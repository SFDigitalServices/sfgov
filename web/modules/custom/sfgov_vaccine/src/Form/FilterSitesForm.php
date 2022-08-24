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
      '#attributes' => [
        'class' => [],
      ],
    ];

    // Single checkboxes.
    $form['container']['toggle']['items']['access'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'mb-40',
        ],
      ],
    ];

    $pediatric_options = [];
    $option_values = $this->settings('form_strings.pediatric.options');
    foreach ($option_values as $value => $label) {
      $pediatric_options[$value] = $this->t($label);
    }

    $form['container']['toggle']['items']['pediatric'] = [
      '#type' => 'select',
      '#title' => $this->t($this->settings('form_strings.pediatric.label')),
      '#default_value' => FALSE,
      '#options' => $pediatric_options,
      '#multiple' => FALSE,
      '#attributes' => [
        'class' => [],
      ],
    ];

    // Single checkboxes - available.
    $form['container']['toggle']['items']['access']['wheelchair'] = [
      '#type' => 'checkbox',
      '#title' => $this->t($this->settings('access_mode.wheelchair.text')),
      '#default_value' => FALSE,
      '#attributes' => [
        'class' => [
          'mb-0',
        ],
      ],
    ];

    // Languages.
    $settings_languages = $this->settings('languages');
    $options_languages = [];
    foreach ($settings_languages as $key => $value) {
      $options_languages[$key] = $this->t($value['filter_label']);
    }

    $form['container']['toggle']['items']['access']['language'] = [
      '#type' => 'select',
      '#title' => $this->t($this->settings('form_strings.language_label')),
      '#title_display' => 'invisible',
      '#options' => $options_languages,
      '#default_value' => 'any',
      '#multiple' => FALSE,
      '#attributes' => [
        'class' => [
          'mb-40',
        ],
      ],
    ];

    // Distance.
    $form['container']['toggle']['items']['distance_from'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'mt-40',
          'flex-nowrap',
        ],
      ],
    ];

    $settings_radius = $this->settings('radius');
    $options_radius = [];
    foreach ($settings_radius as $key => $set) {
      $value = $set['value'];
      $text = $set['text'];
      $options_radius[$value] = $this->t($text);
    }

    $form['container']['toggle']['items']['distance_from']['radius'] = [
      '#type' => 'select',
      '#title' => $this->t($this->settings('form_strings.distance_label')),
      '#options' => $options_radius,
      '#default_value' => 'all',
      '#multiple' => FALSE,
      '#suffix' => $this->t('<span>from</span>'),
      '#attributes' => [
        'class' => ['mb-0'],
      ],
    ];

    $form['container']['toggle']['items']['location'] = [
      '#type' => 'textfield',
      '#title' => $this->t($this->settings('form_strings.location_label')),
      '#title_display' => 'invisible',
      '#attributes' => [
        'placeholder' => $this->t($this->settings('form_strings.location_label')),
        'class' => [],
      ],
    ];

    // Submit.
    $form['container']['toggle']['items']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t($this->settings('form_strings.submit_label')),
      '#attributes' => [
        'class' => [
          'btn',
          'mt-20',
        ],
      ],
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
