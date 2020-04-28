<?php

namespace Drupal\tmgmt\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Entity\Job;

/**
 * Form controller for the job edit forms.
 *
 * @ingroup tmgmt_job
 */
class ContinuousJobForm extends JobForm {

  /**
   * @var \Drupal\tmgmt\JobInterface
   */
  protected $entity;

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {

    $job = $this->entity;
    // Handle source language.
    $available['source_language'] = tmgmt_available_languages();

    // Handle target language.
    $selected = $job->getSourceLangcode() != LanguageInterface::LANGCODE_NOT_SPECIFIED ? $job->getSourceLangcode() : array_keys(tmgmt_available_languages())[0];
    $available['target_language'] = tmgmt_available_languages([$selected]);

    $this->entity->set('job_type', Job::TYPE_CONTINUOUS);

    $form = parent::form($form, $form_state);
    // Set the title of the page to the label and the current state of the job.
    $form['#title'] = (t('@title', array(
      '@title' => 'New Continuous Job',
    )));

    $form['label']['widget'][0]['value']['#description'] = t('You need to provide a label for this job in order to identify it later on.');
    $form['label']['widget'][0]['value']['#required'] = TRUE;

    $form['info']['source_language'] = array(
      '#title' => t('Source language'),
      '#type' => 'select',
      '#options' => $available['source_language'],
      '#default_value' => $job->getSourceLangcode(),
      '#required' => TRUE,
      '#prefix' => '<div id="tmgmt-ui-source-language" class="tmgmt-ui-source-language tmgmt-ui-info-item">',
      '#suffix' => '</div>',
      '#ajax' => array(
        'callback' => array($this, 'ajaxSourceLanguageSelect'),
        'wrapper' => 'tmgmt-ui-target-language',
        'event' => 'change',
      ),
    );

    $form['info']['target_language'] = array(
      '#title' => t('Target language'),
      '#type' => 'select',
      '#options' => $available['target_language'],
      '#default_value' => $job->getTargetLangcode(),
      '#required' => TRUE,
      '#prefix' => '<div id="tmgmt-ui-target-language" class="tmgmt-ui-target-language tmgmt-ui-info-item">',
      '#suffix' => '</div>',
      '#ajax' => array(
        'callback' => array($this, 'ajaxLanguageSelect'),
        'wrapper' => 'tmgmt-ui-target-language',
      ),
      '#validated' => TRUE,
    );

    return $form;
  }

  /**
   * Ajax callback to fetch the options for target language select.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   Target language select array.
   */
  public function ajaxSourceLanguageSelect(array $form, FormStateInterface $form_state) {
    if ($el = $form_state->getTriggeringElement()['#value']) {
      $selected_option = [$el => tmgmt_available_languages()[$el]];
      $options = array_diff(tmgmt_available_languages(), $selected_option);
      $form['info']['target_language']['#options'] = $options;
      return $form['info']['target_language'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save job'),
      '#submit' => array('::submitForm', '::save'),
      '#weight' => 5,
      '#button_type' => 'primary',
    );
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    // Per default we want to redirect the user to the overview.
    $form_state->setRedirect('view.tmgmt_job_overview.page_1');
  }

  /**
   * Custom access check for continuous job form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns allowed if we have a translator with ContinuousSourceInterface
   *   and the logged in user has permission to create translation jobs.
   */
  public function access(AccountInterface $account) {
    if (\Drupal::service('tmgmt.continuous')->checkIfContinuousTranslatorAvailable()) {
      return AccessResult::allowedIfHasPermissions($account, ['administer tmgmt'])
        ->addCacheTags(['config:tmgmt_translator_list']);
    }
    return AccessResult::forbidden()->addCacheTags(['config:tmgmt_translator_list']);
  }

}
