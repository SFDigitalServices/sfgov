<?php

namespace Drupal\tmgmt\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\JobCheckoutManager;
use Drupal\tmgmt\JobInterface;
use Drupal\user\Entity\User;
use Drupal\views\Views;
use Drupal\tmgmt\ContinuousSourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the job edit forms.
 *
 * @ingroup tmgmt_job
 */
class JobForm extends TmgmtFormBase {

  /**
   * @var \Drupal\tmgmt\JobInterface
   */
  protected $entity;

  /**
   * @var \Drupal\tmgmt\JobQueue
   */
  protected $jobQueue;

  /**
   * @var \Drupal\tmgmt\JobCheckoutManager
   */
  protected $jobCheckoutManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->jobQueue = $container->get('tmgmt.queue');
    $form->jobCheckoutManager = $container->get('tmgmt.job_checkout_manager');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    // If the job is submittable and is in the queue, display the progress.
    if ($this->entity->isSubmittable() && $this->jobQueue->isJobInQueue($this->entity) && ($this->jobQueue->count() + $this->jobQueue->getProcessed()) > 1) {
      $total = $this->jobQueue->getProcessed() + $this->jobQueue->count();
      $percent = $this->jobQueue->getProcessed() ? round($this->jobQueue->getProcessed() / $total * 100, 0) : 3;

      $form['progress'] = [
        '#theme' => 'progress_bar',
        '#attached' => ['library' => 'tmgmt/admin'],
        '#percent' => $percent,
        '#weight' => -100,
      ];

      $form['progress_details'] = [
        '#type' => 'details',
        '#title' => $this->getStringTranslation()->formatPlural($this->jobQueue->count(), '@count job pending', '@count jobs pending'),
        '#open' => FALSE,
        '#weight' => -99,
      ];

      $translation = $this->getStringTranslation();
      $item_labels = array_map(function (JobInterface $job) use ($translation) {
        if (!$job->getTargetLangcode() || $job->getTargetLangcode() == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
          $target = '?';
        }
        else {
          $target = $job->getTargetLanguage()->getName();
        }
        return $translation->translate('@label, @source to @target', [
          '@label' => $job->label(),
          '@source' => $job->getSourceLanguage()->getName(),
          '@target' => $target,
        ]);
      }, $this->jobQueue->getAllJobs());

      $form['progress_details']['job_list'] = [
        '#theme' => 'item_list',
        '#items' => $item_labels,
      ];
    }

    $job = $this->entity;
    // Handle source language.
    $available['source_language'] = tmgmt_available_languages();
    $job->source_language = $form_state->getValue('source_language') ?: $job->getSourceLangcode();

    // Handle target language.
    $available['target_language'] = tmgmt_available_languages();
    $job->target_language = $form_state->getValue('target_language') ?: $job->getTargetLangcode();

    // Remove impossible combinations so we don't end up with the same source and
    // target language in the dropdowns.
    foreach (array('source_language' => 'target_language', 'target_language' => 'source_language') as $field_name => $opposite) {
      if (!empty($job->get($field_name)->value)) {
        unset($available[$opposite][$job->get($field_name)->value]);
      }
    }

    $source = $job->getSourceLanguage() ? $job->getSourceLanguage()->getName() : '?';
    if (!$job->getTargetLangcode() || $job->getTargetLangcode() == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      $job->target_language = key($available['target_language']);
      $target = '?';
    }
    else {
      $target = $job->getTargetLanguage()->getName();
    }

    $states = Job::getStates();
    // Set the title of the page to the label and the current state of the job.
    $form['#title'] = (t('@title (@source to @target, @state)', array(
      '@title' => $job->label(),
      '@source' => $source,
      '@target' => $target,
      '@state' => $states[$job->getState()],
    )));

    $form = parent::form($form, $form_state);

    $form['info'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('tmgmt-ui-job-info', 'clearfix')),
      '#weight' => 0,
    );

    // Check for label value and set for dynamically change.
    if ($form_state->getValue('label') && $form_state->getValue('label') == $job->label()) {
      $job->label = NULL;
      $job->label = $job->label();
      $form_state->setValue('label', $job->label());
    }

    $form['label']['widget'][0]['value']['#description'] = t('You can provide a label for this job in order to identify it easily later on. Or leave it empty to use the default one.');
    $form['label']['#group'] = 'info';
    $form['label']['#prefix'] = '<div id="tmgmt-ui-label">';
    $form['label']['#suffix'] = '</div>';

    // Make the source and target language flexible by showing either a select
    // dropdown or the plain string (if preselected).
    if ($job->getSourceLangcode() || !$job->isSubmittable()) {
      $form['info']['source_language'] = array(
        '#title' => t('Source language'),
        '#type' =>  'item',
        '#markup' => isset($available['source_language'][$job->getSourceLangcode()]) ? $available['source_language'][$job->getSourceLangcode()] : '',
        '#prefix' => '<div id="tmgmt-ui-source-language" class="tmgmt-ui-source-language tmgmt-ui-info-item">',
        '#suffix' => '</div>',
        '#value' => $job->getSourceLangcode(),
      );
    }
    else {
      $form['info']['source_language'] = array(
        '#title' => t('Source language'),
        '#type' => 'select',
        '#options' => $available['source_language'],
        '#default_value' => $job->getSourceLangcode(),
        '#required' => TRUE,
        '#prefix' => '<div id="tmgmt-ui-source-language" class="tmgmt-ui-source-language tmgmt-ui-info-item">',
        '#suffix' => '</div>',
        '#ajax' => array(
          'callback' => array($this, 'ajaxLanguageSelect'),
        ),
      );
    }
    if (!$job->isSubmittable()) {
      $form['info']['target_language'] = array(
        '#title' => t('Target language'),
        '#type' => 'item',
        '#markup' => isset($available['target_language'][$job->getTargetLangcode()]) ? $available['target_language'][$job->getTargetLangcode()] : '',
        '#prefix' => '<div id="tmgmt-ui-target-language" class="tmgmt-ui-target-language tmgmt-ui-info-item">',
        '#suffix' => '</div>',
        '#value' => $job->getTargetLangcode(),
      );
    }
    else {
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
      );
    }

    // Display selected translator for already submitted jobs.
    if (!$job->isSubmittable() && !$job->isContinuous()) {
      $form['info']['translator'] = array(
        '#type' => 'item',
        '#title' => t('Provider'),
        '#markup' => $job->getTranslatorLabel(),
        '#prefix' => '<div class="tmgmt-ui-translator tmgmt-ui-info-item">',
        '#suffix' => '</div>',
        '#value' => $job->getTranslatorId(),
      );
    }

    if(!$job->isContinuous()) {
      $form['info']['word_count'] = array(
        '#type' => 'item',
        '#title' => t('Total words'),
        '#markup' => number_format($job->getWordCount()),
        '#prefix' => '<div class="tmgmt-ui-word-count tmgmt-ui-info-item">',
        '#suffix' => '</div>',
      );

      $form['info']['tags_count'] = array(
        '#type' => 'item',
        '#title' => t('Total HTML tags'),
        '#markup' => number_format($job->getTagsCount()),
        '#prefix' => '<div class="tmgmt-ui-tags-count tmgmt-ui-info-item">',
        '#suffix' => '</div>',
      );
    }
    else {
      $roles1 = user_roles(TRUE, 'administer tmgmt');
      $roles2 = user_roles(TRUE, 'create translation jobs');
      $roles3 = user_roles(TRUE, 'submit translation jobs');
      $roles4 = user_roles(TRUE, 'accept translation jobs');
      $duplicates = array_merge($roles1, $roles2, $roles3, $roles4);
      $roles = array_unique($duplicates, SORT_REGULAR);
      if (array_key_exists('authenticated', $roles)) {
        $filter = [];
      }
      else {
        $ids = array_keys($roles);
        $roles = array_combine($ids, $ids);
        $filter = [
          'type' => 'role',
          'role' => $roles,
        ];
      }
      $form['info']['uid'] = array(
        '#title' => t('Owner'),
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#selection_settings' => [
          'include_anonymous' => FALSE,
          'filter' => $filter,
          'field' => 'uid',
        ],
        '#process_default_value' => TRUE,
        '#default_value' => $job->getOwnerId() == 0 ? User::load(\Drupal::currentUser()->id()) : $job->getOwner(),
        '#required' => TRUE,
        '#prefix' => '<div id="tmgmt-ui-owner" class="tmgmt-ui-owner tmgmt-ui-info-item">',
        '#suffix' => '</div>',
      );
    }

    if(!$job->isContinuous()) {
      // Checkout whether given source already has items in translation.
      $num_of_existing_items = count($job->getConflictingItemIds());
      $form['message'] = array(
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => \Drupal::translation()->formatPlural($num_of_existing_items, '1 item conflict with pending item and will be dropped on submission.', '@count items conflict with pending items and will be dropped on submission.'),
        '#prefix' => '<div class="messages existing-items messages--warning hidden">',
        '#suffix' => '</div>',
      );
      if ($num_of_existing_items) {
        $form['message']['#prefix'] = '<div class="messages existing-items messages--warning">';
      }
    }

    // Display created time only for jobs that are not new anymore.
    if (!$job->isUnprocessed() && !$job->isContinuousActive()) {
      $form['info']['created'] = array(
        '#type' => 'item',
        '#title' => t('Created'),
        '#markup' => $this->dateFormatter->format($job->getCreatedTime()),
        '#prefix' => '<div class="tmgmt-ui-created tmgmt-ui-info-item">',
        '#suffix' => '</div>',
        '#value' => $job->getCreatedTime(),
      );
    }
    else {
      // Indicate the state to the forms css classes.
      $form['#attributes']['class'][] = 'state-unprocessed';
    }
    if(!$job->isContinuous()) {
      if ($view = Views::getView('tmgmt_job_items')) {
        $form['job_items_wrapper'] = array(
          '#type' => 'container',
          '#weight' => 10,
          '#prefix' => '<div id="tmgmt-ui-job-checkout-details">',
          '#suffix' => '</div>',
        );
        $form['footer'] = tmgmt_color_job_item_legend();
        $form['footer']['#weight'] = 100;
        // Translation jobs.
        $output = $view->preview($job->isSubmittable() ? 'checkout' : 'submitted', array($job->id()));
        $form['job_items_wrapper']['items'] = array(
          '#type' => 'details',
          '#title' => t('Job items'),
          '#open' => in_array($job->getState(), array(Job::STATE_ACTIVE)),
          '#prefix' => '<div class="' . 'tmgmt-ui-job-items ' . ($job->isSubmittable() ? 'tmgmt-ui-job-submit' : 'tmgmt-ui-job-manage') . '">',
          'view' => $output,
          '#attributes' => array('class' => array('tmgmt-ui-job-items', $job->isSubmittable() ? 'tmgmt-ui-job-submit' : 'tmgmt-ui-job-manage')),
          '#suffix' => '</div>',
        );
      }

      // Always show suggestions when the job has not yet been submitted.
      if ($job->isSubmittable()) {

        // A Wrapper for a button and a table with all suggestions.
        $form['job_items_wrapper']['suggestions'] = array(
          '#type' => 'details',
          '#title' => $this->t('Suggestions'),
          '#open' => TRUE,
          '#access' => $job->isSubmittable(),
        );

        $form['job_items_wrapper']['suggestions']['container'] = array(
          '#type' => 'container',
          '#prefix' => '<div id="tmgmt-ui-job-items-suggestions">',
          '#suffix' => '</div>',
        );

        // Create the suggestions table.
        $suggestions_table = array(
          '#type' => 'tableselect',
          '#header' => array(),
          '#options' => array(),
          '#multiple' => TRUE,
        );

        $this->buildSuggestions($suggestions_table, $form_state);

        // A save button on bottom of the table is needed.
        $form['job_items_wrapper']['suggestions']['container']['suggestions_list'] = array(
          'suggestions_table' => $suggestions_table,
          'suggestions_add' => array(
            '#type' => 'submit',
            '#value' => t('Add suggestions'),
            '#submit' => array('::addSuggestionsSubmit'),
            '#limit_validation_errors' => array(array('suggestions_table')),
            '#attributes' => array(
              'class' => array('tmgmt-ui-job-suggestions-add'),
            ),
          ),
        );

        // Only show suggestions if there are any, in that case collapse
        // the job items list by default.
        $form['job_items_wrapper']['suggestions']['#access'] = !empty($suggestions_table['#options']);
        $form['job_items_wrapper']['items']['#open'] = empty($suggestions_table['#options']);
      }
    }

    if ($job->isContinuous()) {
      $form['continuous_settings'] = array(
        '#type' => 'details',
        '#title' => $this->t('Continuous settings'),
        '#description' => $this->t('Configure the sources that should be enabled for this continuous job.'),
        '#open' => TRUE,
        '#weight' => 10,
        '#tree' => TRUE,
      );

      $source_manager = \Drupal::service('plugin.manager.tmgmt.source');
      $source_plugins = $source_manager->getDefinitions();
      foreach ($source_plugins as $type => $definition) {
        $plugin_type = $source_manager->createInstance($type);
        if ($plugin_type instanceof ContinuousSourceInterface) {
          $form['continuous_settings'][$type] = $plugin_type->continuousSettingsForm($form, $form_state, $job);
        }
      }
    }

    // Display the checkout settings form if the job can be checked out.
    if ($job->isSubmittable() || $job->isContinuous()) {

      $form['translator_wrapper'] = array(
        '#type' => 'details',
        '#title' => t('Configure provider'),
        '#weight' => 20,
        '#prefix' => '<div id="tmgmt-ui-translator-wrapper">',
        '#suffix' => '</div>',
        '#open' => TRUE,
      );

      // Show a list of translators tagged by availability for the selected source
      // and target language combination.
      if (!$translators = tmgmt_translator_labels_flagged($job)) {
        $this->messenger()->addWarning(t('There are no providers available. Before you can checkout you need to @configure at least one provider.', array('@configure' => \Drupal::l(t('configure'), Url::fromRoute('entity.tmgmt_translator.collection')))));
      }
      $preselected_translator = $job->getTranslatorId() && isset($translators[$job->getTranslatorId()]) ? $job->getTranslatorId() : key($translators);
      $job->translator = $form_state->getValue('translator') ?: $preselected_translator;

      $form['translator_wrapper']['translator'] = array(
        '#type' => 'select',
        '#title' => t('Provider'),
        '#description' => t('The configured provider that will process the translation.'),
        '#options' => $translators,
        '#access' => !empty($translators),
        '#default_value' => $job->getTranslatorId(),
        '#required' => TRUE,
        '#ajax' => array(
          'callback' => array($this, 'ajaxTranslatorSelect'),
          'wrapper' => 'tmgmt-ui-translator-wrapper',
        ),
      );

      // Add the provider logo in the settings wrapper.
      /** @var \Drupal\tmgmt\Entity\Translator $entity */
      $definition = \Drupal::service('plugin.manager.tmgmt.translator')->getDefinition($job->getTranslatorPlugin()->getPluginId());
      if (isset($definition['logo'])) {
        $form['translator_wrapper']['logo'] = $logo_render_array = [
          '#theme' => 'image',
          '#uri' => file_create_url(drupal_get_path('module', $definition['provider']) . '/' . $definition['logo']),
          '#alt' => $definition['label'],
          '#title' => $definition['label'],
          '#attributes' => [
            'class' => 'tmgmt-logo-settings',
          ],
          '#suffix' => '<div class="clearfix"></div>',
        ];
      }

      $settings = $this->checkoutSettingsForm($form_state, $job);
      if(!is_array($settings)){
        $settings = array();
      }
      $form['translator_wrapper']['settings'] = array(
        '#type' => 'details',
        '#title' => t('Checkout settings'),
        '#prefix' => '<div id="tmgmt-ui-translator-settings">',
        '#suffix' => '</div>',
        '#tree' => TRUE,
        '#open' => TRUE,
      ) + $settings;

      // If there are additional jobs in the queue, allow to submit them all.
      $count = $this->jobQueue->count();
      if ($count > 1 && !$job->isContinuous()) {
        $form['translator_wrapper']['submit_all'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Submit all @count translation jobs with the same settings', ['@count' => $count]),
          '#description' => $this->t('This will attempt to submit all @count pending translation jobs to the same provider with the configured settings. Jobs that can not be submitted successfully can be re-submitted with different settings.', ['@count' => $count]),
        ];
      }
    }
    // Otherwise display the checkout info.
    elseif ($job->getTranslatorId() && !$job->isContinuous()) {

      $form['translator_wrapper'] = array(
        '#type' => 'details',
        '#title' => t('Provider information'),
        '#open' => TRUE,
        '#weight' => 20,
      );

      $form['translator_wrapper']['checkout_info'] = $this->checkoutInfo($job);
    }

    if (!$job->isContinuous() && !$job->isSubmittable() && empty($form['translator_wrapper']['checkout_info'])) {
      $form['translator_wrapper']['checkout_info'] = array(
        '#type' => 'markup',
        '#markup' => t('The translator does not provide any information.'),
      );
    }

    $form['clearfix'] = array(
      '#markup' => '<div class="clearfix"></div>',
      '#weight' => 45,
    );

    if (!$job->isContinuous() && $view = Views::getView('tmgmt_job_messages')) {
      $output = $view->preview('embed', array($job->id()));
      if ($view->result) {
        $form['messages'] = [
          '#type' => 'details',
          '#title' => $view->storage->label(),
          '#open' => TRUE,
          '#weight' => 50,
        ];
        $form['messages']['view'] = $output;
      }
    }

    $form['#attached']['library'][] = 'tmgmt/admin';
    return $form;
  }

  protected function actions(array $form, FormStateInterface $form_state) {
    $job = $this->entity;

    $actions['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save job'),
      '#submit' => array('::submitForm', '::save'),
      '#weight' => 5,
    );

    if (!$job->isUnprocessed()) {
      $actions['save']['#button_type'] = 'primary';
    }
    if (!$job->isContinuous() && $job->access('submit')) {
      $actions['submit'] = array(
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->jobQueue->count() <= 1 ? t('Submit to provider') : t('Submit to provider and continue'),
        '#access' => $job->isSubmittable(),
        '#disabled' => !$job->getTranslatorId(),
        '#submit' => array('::submitForm', '::save'),
        '#weight' => 0,
      );
      $actions['resubmit_job'] = array(
        '#type' => 'submit',
        '#submit' => array('tmgmt_submit_redirect'),
        '#redirect' => 'admin/tmgmt/jobs/' . $job->id() . '/resubmit',
        '#value' => t('Resubmit'),
        '#access' => $job->isAborted(),
        '#weight' => 10,
      );
      $actions['abort_job'] = array(
        '#type' => 'submit',
        '#value' => t('Abort job'),
        '#redirect' => 'admin/tmgmt/jobs/' . $job->id() . '/abort',
        '#submit' => array('tmgmt_submit_redirect'),
        '#access' => $job->isAbortable(),
        '#weight' => 15,
      );
    }
    else {
      $actions['save']['#button_type'] = 'primary';
    }

    if (!$job->isNew()) {
      $actions['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
        '#submit' => array('tmgmt_submit_redirect'),
        '#redirect' => 'admin/tmgmt/jobs/' . $job->id() . '/delete',
        // Don't run validations, so the user can always delete the job.
        '#limit_validation_errors' => array(),
      );
    }
    return $actions;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt\JobInterface $job */
    $job = parent::validateForm($form, $form_state);
    if ($job->hasTranslator()) {
      $translator = $job->getTranslator();
      // Check translator availability.
      $available_status = $translator->checkAvailable();
      $translatable_status = $translator->checkTranslatable($job);
      if (!($available_status->getSuccess())) {
        $form_state->setErrorByName('translator', $available_status->getReason());
      }
      elseif (!$translatable_status->getSuccess()) {
        $form_state->setErrorByName('translator', $translatable_status->getReason());
      }
    }

    if (!$job->isContinuous() && isset($form['actions']['submit']) && $form_state->getTriggeringElement()['#value'] == $form['actions']['submit']['#value']) {
      $existing_items_ids = $job->getConflictingItemIds();
      $form_state->set('existing_item_ids', $existing_items_ids);

      // If the amount of existing items is the same as the total job item count
      // then the job can not be submitted.
      if (count($job->getItems()) == count($existing_items_ids)) {
        $form_state->setErrorByName('target_language', $this->t('All job items are conflicting, the job can not be submitted.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt\JobInterface $job */
    $job = parent::buildEntity($form, $form_state);

    if ($job->hasTranslator()) {
    $translator = $job->getTranslator();
      // If requested custom job settings handling, copy values from original job.
      if ($translator->hasCustomSettingsHandling()) {
        $original_job = \Drupal::entityTypeManager()->getStorage('tmgmt_job')->loadUnchanged($job->id());
        $job->settings = $original_job->settings;
      }
    }
    // Make sure that we always store a label as it can be a slow operation to
    // generate the default label.
    if (empty($job->label)) {
      $job->label = $job->label();
    }

    return $job;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    // Everything below this line is only invoked if the 'Submit to provider'
    // button was clicked.
    if (isset($form['actions']['submit']) && $form_state->getTriggeringElement()['#value'] == $form['actions']['submit']['#value']) {

      if ($this->entity->get('label')->isEmpty()) {
        $this->entity->set('label', $this->entity->label());
      }

      // Delete conflicting items.
      $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_job_item');
      if ($existing_items_ids = $form_state->get('existing_item_ids')) {
        $storage->delete($storage->loadMultiple($existing_items_ids));
        $num_of_items = count($existing_items_ids);
        $this->messenger()->addWarning(\Drupal::translation()->formatPlural($num_of_items, '1 conflicting item has been dropped.', '@count conflicting items have been dropped.'));
      }

      if ($form_state->getValue('submit_all')) {
        $this->getRequest()->query->remove('destination');

        $batch = array(
          'title' => t('Submitting jobs'),
          'operations' => [],
          'finished' => [JobCheckoutManager::class, 'batchSubmitFinished'],
        );

        foreach ($this->jobQueue->getAllJobs() as $job) {
          $batch['operations'][] = [
            [JobCheckoutManager::class, 'batchSubmit'],
            [$job->id(), $this->entity->id()],
          ];

        }
        batch_set($batch);
        return;
      }

      if (!$this->jobCheckoutManager->requestTranslation($this->entity)) {
        // Don't redirect the user if the translation request failed but retain
        // existing destination parameters so we can redirect once the request
        // finished successfully.
        $this->getRequest()->query->remove('destination');
        return;
      }
      else {
        $this->jobQueue->markJobAsProcessed($this->entity);
      }

      if ($redirect = $this->jobQueue->getNextUrl()) {
        // Proceed to the next redirect queue item, if there is one.
        $form_state->setRedirectUrl($redirect);
      }
      elseif ($destination = $this->jobQueue->getDestination()) {
        // Proceed to the defined destination if there is one.
        $form_state->setRedirectUrl(Url::fromUri('base:' . $destination));
      }
      else {
        // Per default we want to redirect the user to the overview.
        $form_state->setRedirect('view.tmgmt_job_overview.page_1');
      }
    }
    else {
      // Per default we want to redirect the user to the overview.
      $form_state->setRedirect('view.tmgmt_job_overview.page_1');
    }
  }

  /**
   * Helper function for retrieving the job settings form.
   *
   * @todo Make use of the response object here.
   */
  function checkoutSettingsForm(FormStateInterface $form_state, JobInterface $job) {
    $form = array();
    if (!$job->hasTranslator()) {
      return $form;
    }
    $translator = $job->getTranslator();
    $result = $translator->checkAvailable();
    if (!$result->getSuccess()) {
      $form['#description'] = $result->getReason();
      return $form;
    }
    // @todo: if the target language is not defined, the check will not work if the first language in the list is not available.
    $result = $translator->checkTranslatable($job);
    if ($job->getTargetLangcode() && !$result->getSuccess()) {
      $form['#description'] = $result->getReason();
      return $form;
    }
    $plugin_ui = $this->translatorManager->createUIInstance($translator->getPluginId());
    $form = $plugin_ui->checkoutSettingsForm($form, $form_state, $job);
    return $form;
  }

  /**
   * Helper function for retrieving the rendered job checkout information.
   */
  function checkoutInfo(JobInterface $job) {
    // The translator might have been disabled or removed.
    if (!$job->hasTranslator()) {
      return array('#markup' => t('The job has no provider assigned.'));
    }
    $translator = $job->getTranslator();
    $plugin_ui = $this->translatorManager->createUIInstance($translator->getPluginId());
    return $plugin_ui->checkoutInfo($job);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->entity->toUrl('delete-form'));
  }

  /**
   * Ajax callback to fetch the supported translator services and rebuild the
   * target / source language dropdowns.
   */
  public function ajaxLanguageSelect(array $form, FormStateInterface $form_state) {
    $number_of_existing_items = count($this->entity->getConflictingItemIds());
    $replace = $form_state->getUserInput()['_triggering_element_name'] == 'source_language' ? 'target_language' : 'source_language';
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#tmgmt-ui-translator-wrapper', $form['translator_wrapper']));
    $response->addCommand(new ReplaceCommand('#tmgmt-ui-' . str_replace('_', '-', $replace), $form['info'][$replace]));
    if ($number_of_existing_items) {
      $response->addCommand(new InvokeCommand('.existing-items', 'removeClass', array('hidden')));
      $response->addCommand(new ReplaceCommand('.existing-items > div', \Drupal::translation()->formatPlural($number_of_existing_items, '1 item conflict with pending item and will be dropped on submission.', '@count items conflict with pending items and will be dropped on submission.')));
    }
    else {
      $response->addCommand(new InvokeCommand('.existing-items', 'addClass', array('hidden')));
    }
    return $response;
  }

  /**
   * Ajax callback to fetch the options provided by a translator.
   */
  public function ajaxTranslatorSelect(array $form, FormStateInterface $form_state) {
    return $form['translator_wrapper'];
  }

  /**
   * Adds selected suggestions to the job.
   */
  function addSuggestionsSubmit(array $form, FormStateInterface $form_state) {
    // Save all selected suggestion items.
    if (is_array($form_state->getValue('suggestions_table'))) {
      $job = $form_state->getFormObject()->getEntity();
      foreach ($form_state->getValue('suggestions_table') as $id) {
        $key = (int)$id - 1; // Because in the tableselect we need an idx > 0.
        if (isset($form_state->get('tmgmt_suggestions')[$key]['job_item'])) {
          $item = $form_state->get('tmgmt_suggestions')[$key]['job_item'];
          $job->addExistingItem($item);
        }
      }
    }

    // Force a rebuild of the form.
    $form_state->setRebuild();
    $form_state->set('tmgmt_suggestions', NULL);
  }

  /**
   * Fills the tableselect with all translation suggestions.
   *
   * Calls hook_tmgmt_source_suggestions(Job) and creates the resulting list
   * based on the results from all modules.
   *
   * @param array $suggestions_table
   *   Tableselect part for a $form array where the #options should be inserted.
   * @param array $form_state
   *   The main form_state.
   */
  function buildSuggestions(array &$suggestions_table, FormStateInterface $form_state) {
    $options = array();
    $job = $form_state->getFormObject()->getEntity();
    if ($job instanceof Job) {
      // Get all suggestions from all modules which implements
      // 'hook_tmgmt_source_suggestions' and cache them in $form_state.
      if (!$form_state->get('tmgmt_suggestions')) {
        $form_state->set('tmgmt_suggestions', $job->getSuggestions());
      }

      // Remove suggestions which are already processed, translated, ...
      $job->cleanSuggestionsList($form_state->get('tmgmt_suggestions'));

      // Process all valid entries.
      foreach ($form_state->get('tmgmt_suggestions') as $k => $result) {
        if (is_array($result) && isset($result['job_item']) && ($result['job_item'] instanceof JobItem)) {
          $options[$k + 1] = $this->addSuggestionItem($result);
        }
      }

      $suggestions_table['#options'] = $options;
      $suggestions_table['#empty'] = t('No related suggestions available.');
      $suggestions_table['#header'] = array(
        'title' => t('Label'),
        'type' => t('Type'),
        'reason' => t('Reason'),
        'words' => t('Word count'),
      );
    }
  }

  /**
   * Create a Suggestion-Table entry based on a Job and a title.
   *
   * @param array $result
   *   Suggestion array with the keys job_item, reason and from_item.
   *
   * @return array
   *   Options-Entry for a tableselect array.
   */
  function addSuggestionItem(array $result) {
    $item = $result['job_item'];

    $reason = isset($result['reason']) ? $result['reason'] : NULL;
    $option = array(
      'title' => $item->label(),
      'type' => $item->getSourceType(),
      'words' => $item->getWordCount(),
      'tags' => $item->getTagsCount(),
      'reason' => $reason,
    );

    if (!empty($result['from_item'])) {
      $from_item = JobItem::load($result['from_item']);
      if ($from_item) {
        $option['reason'] = t('%reason in %job', array('%reason' => $option['reason'], '%job' => $from_item->label()));
      }
    }
    return $option;
  }

  /**
   * Handles submit call to rebuild a job.
   */
  public function submitBuildJob(array $form, FormStateInterface $form_state) {
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state->setRebuild();
  }

}
