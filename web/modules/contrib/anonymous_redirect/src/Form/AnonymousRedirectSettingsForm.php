<?php

/**
 * @file
 * Contains \Drupal\anonymous_redirect\Form\AnonymousRedirectSettingsForm.
 */

namespace Drupal\anonymous_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AnonymousRedirectSettingsForm.
 *
 * @package Drupal\anonymous_redirect\Form
 */
class AnonymousRedirectSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_redirect_settings_form';
  }




  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'anonymous_redirect.settings',
    ];
  }




  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('anonymous_redirect.settings');

    $form['enable_anonymous_redirect'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Anonymous Redirect'),
      '#description' => $this->t('turn on/off anonymous redirect'),
      '#default_value' => $config->get('enable_redirect'),
    );
    $form['redirect_base_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Redirect Base URL'),
      '#description' => $this->t("For internal URL's use " . "<front>" . " or '/path' .For external ULR's user http:// and No trailing slash. For example, http://example.com or http://example.com/drupal."),
      '#maxlength' => 500,
      '#size' => 64,
      "#default_value" => $config->get('redirect_url'),
    );

    $form['redirect_url_overrides'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Redirect URL Overrides'),
      '#description' => $this->t("A list of internal paths to ignore the redirect for. One path per line. (eg. '/path')"),
      '#rows' => 4,
      '#default_value' => $config->get('redirect_url_overrides'),
    );

    return parent::buildForm($form, $form_state);
  }




  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('anonymous_redirect.settings')->set('enable_redirect', $form_state->getValue('enable_anonymous_redirect'));
    $this->config('anonymous_redirect.settings')->set('redirect_url',$form_state->getValue('redirect_base_url'));
    $this->config('anonymous_redirect.settings')->set('redirect_url_overrides',$form_state->getValue('redirect_url_overrides'));

    $this->config('anonymous_redirect.settings')->save();

    // forces a cache rebuild so that the changes take effect as soon as the form is saved
    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
