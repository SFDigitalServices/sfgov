<?php

namespace Drupal\sfgov_vaccine\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FilterSitesForm.
 */
class FilterSitesForm extends FormBase {

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
    $form['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Filters'),
      ];
    $form['restrictions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only show sites open to the general public'),
      '#default_value' => TRUE,
    ];
    $form['available'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only show sites with available appointments'),
      '#default_value' => FALSE,
    ];
    $form['wheelchair'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wheelchair accessible'),
      '#default_value' => FALSE,
    ];
    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#title_display' => 'hidden',
      '#options' => [
        // @todo refactor with VaccineController->makeResults() for DRYness.
        'all' => $this->t('Any language'),
        'en' => $this->t('English'),
        'es' => $this->t('Spanish'),
        'zh' => $this->t('Chinese'),
        'fil' => $this->t('Filipino'),
        'vi' => $this->t('Vietnamese'),
        'ru' => $this->t('Russian'),
      ],
      '#default_value' => 'any',
      '#multiple' => FALSE,
    ];
    $form['access_mode'] = [
      '#type' => 'select',
      '#title_display' => 'hidden',
      '#options' => [
        'all' => $this->t('Drive-thru and walk-thru'),
        'dr' => $this->t('Drive-thru'),
        'wa' => $this->t('Walk-thru'),
      ],
      '#default_value' => 'all',
      '#multiple' => FALSE,
    ];
    $form['eligibility'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Eligibility requirements '),
      '#options' => [
        'sf' => $this->t('65 and over'),
        'hw' => $this->t('Healthcare workers'),
        'ec' => $this->t('Education and childcare'),
        'af' => $this->t('Agriculture and food'),
//        'sd' => $this->t('Second dose'),
        'es' => $this->t('Emergency services'),
        ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return NULL;
  }

}
