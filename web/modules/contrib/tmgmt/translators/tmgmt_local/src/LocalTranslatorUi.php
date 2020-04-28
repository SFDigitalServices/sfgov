<?php

namespace Drupal\tmgmt_local;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\user\Entity\User;

/**
 * Drupal user provider plugin UI.
 */
class LocalTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    if ($translators = tmgmt_local_assignees($job->getSourceLangcode(), array($job->getTargetLangcode()))) {
      $form['translator'] = array(
        '#title' => t('Assign job to'),
        '#type' => 'select',
        '#options' => array('' => t('- Select user -')) + $translators,
        '#default_value' => $job->getSetting('translator'),
      );
    }
    else {
      $form['message'] = array(
        '#markup' => t('There are no users available to assign.'),
      );
    }
    $form['job_comment'] = array(
      '#type' => 'textarea',
      '#title' => t('Comment for the translation'),
      '#description' => t('You can provide a comment so that the assigned user will better understand your requirements.'),
      '#default_value' => $job->getSetting('job_comment'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {
    $tuid = $job->getSetting('translator');
    if ($tuid && $translator = User::load($tuid)) {
      $form['job_status'] = array(
        '#type' => 'item',
        '#title' => t('Job status'),
        '#markup' => t('Translation job is assigned to %name.', array('%name' => $translator->getDisplayName())),
      );
    }
    else {
      $form['job_status'] = array(
        '#type' => 'item',
        '#title' => t('Job status'),
        '#markup' => t('Translation job is not assigned to any user.'),
      );
    }

    if ($job->getSetting('job_comment')) {
      $form['job_comment'] = array(
        '#type' => 'item',
        '#title' => t('Job comment'),
        '#markup' => Xss::filter($job->getSetting('job_comment')),
      );
    }

    return $form;
  }

}
