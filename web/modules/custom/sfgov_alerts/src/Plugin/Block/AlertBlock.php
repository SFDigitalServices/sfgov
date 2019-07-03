<?php

namespace Drupal\sfgov_alerts\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'AlertBlock' block.
 *
 * @Block(
 *  id = "alert_block",
 *  admin_label = @Translation("Alert block"),
 * )
 */
class AlertBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'alert_expiration' => '',
      'alert_text' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['alert_expiration'] = [
      '#type' => 'date',
      '#title' => $this->t('Expiration Date'),
      '#description' => $this->t('Alert will be hidden on this date.'),
      '#default_value' => $this->configuration['alert_expiration'],
      '#weight' => '4',
    ];

    $form['alert_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Alert Text'),
      '#description' => $this->t('Enter your text'),
      '#default_value' => $this->configuration['alert_text'],
      '#weight' => '4',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->configuration['alert_text'] = $form_state->getValue('alert_text');
    $this->configuration['alert_expiration'] = $form_state->getValue('alert_expiration');

    $log_message = t('@expiration - @text', [
      '@text' => $this->configuration['alert_text'],
      '@expiration' => $this->configuration['alert_expiration']
    ]);

    $display_message = t('Alert Expiration Date: @expiration. Alert Text: @text', [
      '@text' => $this->configuration['alert_text'],
      '@expiration' => $this->configuration['alert_expiration']
    ]);

    \Drupal::logger('sfgov_alerts')->info($log_message);
    \Drupal::messenger()->addMessage($display_message);

  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    if ($this->configuration['alert_expiration'] < date('Y-m-d')) {
      $build = [];
    }

    else {
      $build['#theme'] = 'sfgov_alerts';
      $build['alert']['#type'] = 'html_tag';
      $build['alert']['#tag'] = 'p';
      $build['alert']['#value'] = $this->configuration['alert_text'];
    }

    return $build;
  }
}
