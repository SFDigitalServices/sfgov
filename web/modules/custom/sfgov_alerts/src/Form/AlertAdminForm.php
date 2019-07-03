<?php

namespace Drupal\sfgov_alerts\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AlertAdminForm.
 */
class AlertAdminForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alert_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['expiration_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Expiration Date'),
      '#description' => $this->t('Alert will be hidden on this date.'),
      '#default_value' => '',
      '#weight' => '0',
    ];
    $form['alert_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Alert Text'),
      '#description' => $this->t('Enter your text'),
      '#default_value' => t("My awesome alert"),
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
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
    ksm($form);

    if ($form['expiration_date']['#value'] != '') {
      \Drupal::messenger()->addMessage(
        t("This alert will expire on " . $form['expiration_date']['#value'])
      );
    } else {
      \Drupal::messenger()->addMessage(
        t("No expiration date has been set.")
      );
    }
  }
}
