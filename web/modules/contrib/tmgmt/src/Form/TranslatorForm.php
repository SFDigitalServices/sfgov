<?php

namespace Drupal\tmgmt\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityForm;
use Drupal\tmgmt\SourceManager;
use Drupal\tmgmt\TranslatorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Form controller for the translator edit forms.
 *
 * @ingroup tmgmt_translator
 */
class TranslatorForm extends EntityForm {

  /**
   * @var \Drupal\tmgmt\TranslatorInterface
   */
  protected $entity;

  /**
   * Translator plugin manager.
   *
   * @var \Drupal\tmgmt\TranslatorManager
   */
  protected $translatorManager;

  /**
   * Source plugin manager.
   *
   * @var \Drupal\tmgmt\SourceManager
   */
  protected $sourceManager;

  /**
   * Constructs an EntityForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   * @param \Drupal\tmgmt\TranslatorManager $translator_manager
   *   The translator plugin manager.
   */
  public function __construct(TranslatorManager $translator_manager, SourceManager $source_manager) {
    $this->translatorManager = $translator_manager;
    $this->sourceManager = $source_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.tmgmt.translator'),
      $container->get('plugin.manager.tmgmt.source')
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if ($this->operation == 'clone') {
      $this->entity = $this->entity->createDuplicate();
    }
    $entity = $this->entity;
    // Check if the translator is currently in use.
    if ($busy = !$entity->isNew() ? tmgmt_translator_busy($entity->id()) : FALSE) {
      \Drupal::messenger()->addWarning(t("This provider is currently in use. It cannot be deleted. The chosen provider Plugin cannot be changed."));
    }
    $available = $this->translatorManager->getLabels();
    // If the translator plugin is not set, pick the first available plugin as the
    // default.
    if (!($entity->hasPlugin())) {
      $entity->setPluginID(key($available));
    }
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#description' => t('The label of the provider.'),
      '#default_value' => $entity->label(),
      '#required' => TRUE,
      '#size' => 32,
      '#maxlength' => 64,
    );
    $form['name'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine name'),
      '#description' => t('The machine readable name of this provider. It must be unique, and it must contain only alphanumeric characters and underscores. Once created, you will not be able to change this value!'),
      '#default_value' => $entity->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\tmgmt\Entity\Translator::load',
        'source' => array('label'),
      ),
      '#disabled' => !$entity->isNew(),
      '#size' => 32,
      '#maxlength' => 64,
    );
    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#description' => t('The description of the provider.'),
      '#default_value' => $entity->getDescription(),
      '#size' => 32,
      '#maxlength' => 255,
    );
    $form['auto_accept'] = array(
      '#type' => 'checkbox',
      '#title' => t('Auto accept finished translations'),
      '#description' => t('This skips the reviewing process and automatically accepts all translations as soon as they are returned by the translation provider.'),
      '#default_value' => $entity->isAutoAccept(),
    );
    $form['plugin_wrapper'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="tmgmt-plugin-wrapper">',
      '#suffix' => '</div>',
    );
    // Pull the translator plugin info if any.
    if ($entity->hasPlugin()) {
      $definition = $this->translatorManager->getDefinition($entity->getPluginId());
      $form['plugin_wrapper']['plugin'] = array(
        '#type' => 'select',
        '#title' => t('Provider plugin'),
        '#submit' => array('::updateRemoteLanguagesMappings'),
        '#limit_validation_errors' => array(array('plugin')),
        '#executes_submit_callback' => TRUE,
        '#description' => isset($definition['description']) ? Xss::filter($definition['description']) : '',
        '#options' => $available,
        '#default_value' => $entity->getPluginId(),
        '#required' => TRUE,
        '#disabled' => $busy,
        '#ajax' => array(
          'callback' => array($this, 'ajaxTranslatorPluginSelect'),
          'wrapper' => 'tmgmt-plugin-wrapper',
        ),
      );

      // Add the provider logo in the settings wrapper.
      if (isset($definition['logo'])) {
        $form['plugin_wrapper']['logo'] = $logo_render_array = [
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

      $form['plugin_wrapper']['settings'] = array(
        '#type' => 'details',
        '#title' => t('@plugin plugin settings', array('@plugin' => $definition['label'])),
        '#tree' => TRUE,
        '#open' => TRUE,
      );

      // Add the translator plugin settings form.
      $plugin_ui = $this->translatorManager->createUIInstance($entity->getPluginId());
      $form_state->set('busy', $busy);
      $form['plugin_wrapper']['settings'] += $plugin_ui->buildConfigurationForm($form['plugin_wrapper']['settings'], $form_state);
      if (!Element::children($form['plugin_wrapper']['settings'])) {
        $form['#description'] = t("The @plugin plugin doesn't provide any settings.", array('@plugin' => $plugin_ui->getPluginDefinition()['label']));
      }

      // If current translator is configured to provide remote language mapping
      // provide the form to configure mappings, unless it does not exists yet.
      if ($entity->providesRemoteLanguageMappings()) {
        $form['plugin_wrapper']['remote_languages_mappings'] = array(
          '#tree' => TRUE,
          '#type' => 'details',
          '#title' => t('Remote languages mappings'),
          '#description' => t('Here you can specify mappings of your local language codes to the translator language codes.'),
          '#open' => TRUE,
        );

        $options = $entity->getSupportedRemoteLanguages();
        foreach ($entity->getRemoteLanguagesMappings() as $local_language => $remote_language) {
          $form['plugin_wrapper']['remote_languages_mappings'][$local_language] = array(
            '#type' => 'textfield',
            '#title' => \Drupal::languageManager()
              ->getLanguage($local_language)
              ->getName() . ' (' . $local_language . ')',
            '#default_value' => $remote_language,
            '#size' => 6,
          );

          if (!empty($options)) {
            $form['plugin_wrapper']['remote_languages_mappings'][$local_language]['#type'] = 'select';
            $form['plugin_wrapper']['remote_languages_mappings'][$local_language]['#options'] = $options;
            $form['plugin_wrapper']['remote_languages_mappings'][$local_language]['#empty_option'] = ' - ';
            unset($form['plugin_wrapper']['remote_languages_mappings'][$local_language]['#size']);
          }
        }
      }
    }

    $form['#attached']['library'][] = 'tmgmt/admin';

    return $form;
  }

  /**
   * Updates remote languages mappings.
   *
   * @param array $form
   *   An associative array containing the initial structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public static function updateRemoteLanguagesMappings(array $form, FormStateInterface $form_state) {
    if (!empty($form_state->getUserInput()['remote_languages_mappings'])) {
      // The user input containing remote languages mappings from an old
      // translator, so We have to remove them from here.
      $user_input = $form_state->getUserInput();
      unset($user_input['remote_languages_mappings']);
      $form_state->setUserInput($user_input);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (!$form_state->getValue('plugin')) {
      $form_state->setErrorByName('plugin', $this->t('You have to select a translator plugin.'));
    }
    $plugin_ui = $this->translatorManager->createUIInstance($this->entity->getPluginID());
    $plugin_ui->validateConfigurationForm($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    if ($status === SAVED_UPDATED) {
      \Drupal::messenger()->addStatus(format_string('%label configuration has been updated.', array('%label' => $entity->label())));
    }
    else {
      \Drupal::messenger()->addStatus(format_string('%label configuration has been created.', array('%label' => $entity->label())));
    }

    $form_state->setRedirect('entity.tmgmt_translator.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->entity->toUrl('delete-form'));
  }

  /**
   * Ajax callback for loading the translator plugin settings form for the
   * currently selected translator plugin.
   */
  public static function ajaxTranslatorPluginSelect(array $form, FormStateInterface $form_state) {
    return $form['plugin_wrapper'];
  }

}
