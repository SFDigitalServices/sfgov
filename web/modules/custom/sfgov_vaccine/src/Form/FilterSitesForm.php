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
    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => [
        // @todo refactor with VaccineController->makeResults() for DRYness.
        'any' => $this->t('Any language'),
        'en' => $this->t('English'),
        'es' => $this->t('Spanish'),
        'zh' => $this->t('Chinese'),
        'fil' => $this->t('Filipino'),
      ],
      '#default_value' => 'any',
      '#multiple' => FALSE,
    ];
    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => [
        'all' => $this->t('Drive-thru and walk-thru'),
        'drive' => $this->t('Drive-thru'),
        'walk' => $this->t('Walk-thru'),
      ],
      '#default_value' => 'all',
      '#multiple' => FALSE,
    ];
    $form['eligibility'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Eligibility requirements '),
      '#options' => [
        '75+' => $this->t('Age 75 and over'),
        '65+' => $this->t('Drive-thru'),
        'frontline' => $this->t('Frontline healthcare workers'),
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
