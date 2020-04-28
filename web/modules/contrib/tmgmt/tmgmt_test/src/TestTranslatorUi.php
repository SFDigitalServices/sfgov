<?php

namespace Drupal\tmgmt_test;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;

/**
 * Test translator UI controller.
 */
class TestTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $test_translator */
    $test_translator = $form_state->getFormObject()->getEntity();

    $form['expose_settings'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display settings'),
      '#default_value' => $test_translator->getSetting('expose_settings'),
    );

    $form['action'] = array(
      '#type' => 'select',
      '#title' => t('Default action'),
      '#options' => array(
        'translate' => t('Translate'),
        'submit' => t('Submit'),
        'reject' => t('Reject'),
        'fail' => t('Fail'),
        'not_available' => t('Not available'),
        'not_translatable' => t('Not translatable'),
      ),
      '#default_value' => $test_translator->getSetting('action'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    if ($job->getTranslator()->getSetting('expose_settings')) {
      $form['action'] = array(
        '#type' => 'select',
        '#title' => t('Action'),
        '#options' => array(
          'translate' => t('Translate'),
          'submit' => t('Submit'),
          'reject' => t('Reject'),
          'fail' => t('Fail'),
          'not_available' => t('Not available'),
          'not_translatable' => t('Not translatable'),
        ),
        '#default_value' => $job->getTranslator()->getSetting('action'),
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function reviewDataItemElement(array $form, FormStateInterface $form_state, $data_item_key, $parent_key, array $data_item, JobItemInterface $item) {
    $form['below']['test_translator'] = array(
      '#markup' => t('Testing output of review data item element @key from the testing provider.', array('@key' => $data_item_key)),
      '#weight' => 100,
    );

    return $form;
  }

}
