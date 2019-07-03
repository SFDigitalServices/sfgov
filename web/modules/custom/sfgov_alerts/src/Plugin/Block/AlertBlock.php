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
            'number' => 5,
          ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['expiration_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Expiration Date'),
      '#description' => $this->t('Alert will be hidden on this date.'),
      '#default_value' => '',
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
    $this->configuration['number'] = $form_state->getValue('number');
    $this->configuration['alert_text'] = $form_state->getValue('alert_text');
    $this->configuration['expiration_date'] = $form_state->getValue('expiration_date');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['#theme'] = 'sfgov_alerts';
    $build['alert']['#type'] = 'html_tag';
    $build['alert']['#tag'] = 'p';
    $build['alert']['#value'] = $this->configuration['alert_text'];

    return $build;
  }
}
