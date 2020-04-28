<?php

namespace Drupal\tmgmt;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides functionality related to job checkout and submissions.
 *
 * @ingroup tmgmt_job
 */
class JobCheckoutManager {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\tmgmt\JobQueue
   */
  protected $jobQueue;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(RequestStack $request_stack, JobQueue $job_queue, ModuleHandler $module_handler, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->requestStack = $request_stack;
    $this->jobQueue = $job_queue;
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Attempts to checkout a number of jobs and prepare the necessary redirects.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state array, used to set the initial redirect.
   * @param \Drupal\tmgmt\JobInterface[] $jobs
   *   Array of jobs to attempt checkout
   *
   * @ingroup tmgmt_job
   */
  public function checkoutAndRedirect(FormStateInterface $form_state, array $jobs) {
    $checkout_jobs = $this->checkoutMultiple($jobs);

    $jobs_ready_for_checkout = array_udiff($jobs, $checkout_jobs, function (JobInterface $a, JobInterface $b) {
      if ($a->id() < $b->id()) {
        return -1;
      }
      elseif ($a->id() > $b->id()) {
        return 1;
      }
      else {
        return 0;
      }
    });

    // If necessary, do a redirect.
    if ($checkout_jobs || $jobs_ready_for_checkout) {
      $request = $this->requestStack->getCurrentRequest();
      if ($request->query->has('destination')) {
        // Remove existing destination, as that will prevent us from being
        // redirect to the job checkout page. Set the destination as the final
        // redirect instead.
        $redirect = $request->query->get('destination');
        $request->query->remove('destination');
      }
      else {
        $redirect = Url::fromRoute('<current>')->getInternalPath();
      }
      $this->jobQueue->startQueue(array_merge($checkout_jobs, $jobs_ready_for_checkout), $redirect);

      // Prepare a batch job for the jobs that can be submitted already.
      if ($jobs_ready_for_checkout) {
        $batch = array(
          'title' => t('Submitting jobs'),
          'operations' => [],
          'finished' => [JobCheckoutManager::class, 'batchSubmitFinished'],
        );

        foreach ($jobs_ready_for_checkout as $job) {
          $batch['operations'][] = [
            [JobCheckoutManager::class, 'batchSubmit'],
            [$job->id(), NULL],
          ];

        }
        batch_set($batch);
      }
      else {
        $form_state->setRedirectUrl($this->jobQueue->getNextUrl());
      }

      // Count of the job messages is one less due to the final redirect.
      $this->messenger()->addStatus($this->getStringTranslation()->formatPlural(count($checkout_jobs), t('One job needs to be checked out.'), t('@count jobs need to be checked out.')));
    }
  }

  /**
   * Attempts to check out a number of jobs.
   *
   * Performs a number of checks on each job and also allows to alter the
   * behavior through hooks.
   *
   * @param \Drupal\tmgmt\JobInterface[] $jobs
   *   The jobs to be checked out.
   * @param bool $skip_request_translation
   *   (optional) If TRUE, the jobs that can be submitted immediately will be
   *   prepared but not submitted yet. They will not be returned, the caller
   *   is responsible for submitting them.
   *
   * @return \Drupal\tmgmt\JobInterface[]
   *   List of jobs that have not been submitted immediately and need to be
   *   processed.
   *
   * @ingroup tmgmt_job
   *
   * @see \Drupal\tmgmt\JobCheckoutManager::checkoutAndRedirect()
   */
  public function checkoutMultiple(array $jobs, $skip_request_translation = FALSE) {
    $remaining_jobs = array();
    // Allow other modules to jump in and eg. auto-checkout with rules or use a
    // customized checkout form.
    $this->moduleHandler->alter('tmgmt_job_checkout_before', $remaining_jobs, $jobs);
    $denied = 0;
    foreach ($jobs as $job) {
      if (!$job->isUnprocessed()) {
        // Job is already checked out, just ignore that one. This could happen
        // if jobs have already been submitted in the before hook.
        continue;
      }
      if (!$this->configFactory->get('tmgmt.settings')->get('quick_checkout') || $this->needsCheckoutForm($job)) {

        if (!$job->access('submit')) {
          // Ignore jobs if the user is not allowed to submit, ignore.
          $denied++;
          // Make sure that the job is saved.
          $job->save();
          continue;
        }

        $remaining_jobs[] = $job;
      }
      else {
        // No manual checkout required. Request translations now, save the job
        // in case someone excepts to be able to load the job and have the
        // translator available.
        $job->save();
        if (!$skip_request_translation) {
          $this->requestTranslation($job);
        }
      }
    }
    // Allow other modules to jump in and eg. auto-checkout with rules or use a
    // customized checkout form.
    $this->moduleHandler->alter('tmgmt_job_checkout_after', $remaining_jobs, $jobs);

    // Display message for created jobs that can not be checked out.
    if ($denied) {
      $this->messenger()->addStatus($this->getStringTranslation()->formatPlural($denied, 'One job has been created.', '@count jobs have been created.'));
    }

    return $remaining_jobs;
  }

  /**
   * Check if a job needs a checkout form.
   *
   * The current checks include if there is more than one translator available,
   * if he has settings and if the job has a fixed target language.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The job item.
   *
   * @return bool
   *   TRUE if the job needs a checkout form.
   */
  public function needsCheckoutForm(JobInterface $job) {
    // If the job has no target language (or source language, even though this
    // should never be the case in our use case), checkout is mandatory.
    if (!$job->getTargetLangcode() || !$job->getSourceLangcode()) {
      return TRUE;
    }
    // If no translator is pre-selected, try to pick one automatically.
    if (!$job->hasTranslator()) {
      // If there is more than a single translator available or if there are no
      // translators available at all checkout is mandatory.
      $translators = tmgmt_translator_load_available($job);
      if (empty($translators) || count($translators) > 1) {
        return TRUE;
      }
      $translator = reset($translators);
      $job->translator = $translator->id();
    }
    // If that translator has settings, the checkout is mandatory.
    if ($job->getTranslator()->hasCheckoutSettings($job)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Batch dispatch callback for submitting a job.
   *
   * @param int $job_id
   *   The job ID to submit.
   * @param int|null $template_job_id
   *   (optional) A template job to use for the translator and settings.
   */
  static public function batchSubmit($job_id, $template_job_id = NULL, &$context) {
    \Drupal::service('tmgmt.job_checkout_manager')->doBatchSubmit($job_id, $template_job_id);
  }

  /**
   * Batch callback for submitting a job.
   *
   * @param int $job_id
   *   The job ID to submit.
   * @param int|null $template_job_id
   *   (optional) A template job to use for the translator and settings.
   */
  public function doBatchSubmit($job_id, $template_job_id = NULL) {
    /** @var \Drupal\tmgmt\JobInterface $job */
    $job = $this->entityTypeManager->getStorage('tmgmt_job')->load($job_id);
    if (!$job) {
      return;
    }

    // Delete duplicates.
    if ($existing_items_ids = $job->getConflictingItemIds()) {
      $item_storage = $this->entityTypeManager->getStorage('tmgmt_job_item');
      if (count($existing_items_ids) == $job->getItems()) {
        $this->messenger()->addStatus($this->t('All job items for job @label are conflicting, the job can not be submitted.', ['@label' => $job->label()]));
        return;
      }
      $item_storage->delete($item_storage->loadMultiple($existing_items_ids));
      $num_of_items = count($existing_items_ids);
      $this->messenger()->addWarning($this->getStringTranslation()->formatPlural($num_of_items, '1 conflicting item has been dropped for job @label.', '@count conflicting items have been dropped for job @label.', ['@label' => $job->label()]));
    }

    if ($template_job_id && $job_id != $template_job_id) {
      /** @var \Drupal\tmgmt\JobInterface $template_job */
      $template_job = $this->entityTypeManager->getStorage('tmgmt_job')->load($template_job_id);
      if ($template_job) {
        $job->set('translator', $template_job->getTranslatorId());
        $job->set('settings', $template_job->get('settings')->getValue());

        // If there is a custom label on the template job, copy that as well.
        if ($template_job->get('label')->value) {
          $job->set('label', $template_job->get('label')->value);
        }
      }
    }

    $translator = $job->getTranslator();
    // Check translator availability.
    $translatable_status = $translator->checkTranslatable($job);
    if (!$translatable_status->getSuccess()) {
      $this->messenger()->addError($this->t('Job @label is not translatable with the chosen settings: @reason', ['@label' => $job->label(), '@reason' => $translatable_status->getReason()]));
      return;
    }

    if ($this->requestTranslation($job)) {
      $this->jobQueue->markJobAsProcessed($job);
    }
  }

  /**
   * Batch dispatch submission finished callback.
   */
  public static function batchSubmitFinished($success, $results, $operations) {
    return \Drupal::service('tmgmt.job_checkout_manager')->doBatchSubmitFinished($success, $results, $operations);
  }

  /**
   * Batch submission finished callback.
   */
  public function doBatchSubmitFinished($success, $results, $operations) {
    if ($redirect = $this->jobQueue->getNextUrl()) {
      // Proceed to the next redirect queue item, if there is one.
      return new RedirectResponse($redirect->setAbsolute()->toString());
    }
    elseif ($destination = $this->jobQueue->getDestination()) {
      // Proceed to the defined destination if there is one.
      return new RedirectResponse(Url::fromUri('base:' . $destination)->setAbsolute()->toString());
    }
    else {
      // Per default we want to redirect the user to the overview.
      return new RedirectResponse(Url::fromRoute('view.tmgmt_job_overview.page_1')->setAbsolute()->toString());
    }
  }

  /**
   * Requests translations for a job and prints messages which have happened since
   * then.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The job object for which translations should be requested.
   *
   * @return bool
   *   TRUE if it worked, FALSE if there were any errors of the type error which
   *   means that something did go wrong.
   */
  function requestTranslation(JobInterface $job) {
    // Process the translation request.
    $job->requestTranslation();
    return tmgmt_write_request_messages($job);
  }

}
