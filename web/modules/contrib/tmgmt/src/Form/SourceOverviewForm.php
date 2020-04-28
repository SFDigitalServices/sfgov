<?php

namespace Drupal\tmgmt\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\tmgmt\ContinuousManager;
use Drupal\tmgmt\ContinuousSourceInterface;
use Drupal\tmgmt\SourceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Source overview form.
 */
class SourceOverviewForm extends FormBase {

  const ALL = '_all';
  const MULTIPLE = '_multiple';
  const SOURCE = '_source';

  /**
   * The source manager.
   *
   * @var \Drupal\tmgmt\SourceManager
   */
  protected $sourceManager;

  /**
   * The continuous manager.
   *
   * @var \Drupal\tmgmt\ContinuousManager
   */
  protected $continuousManager;

  /**
   * Constructs a new SourceLocalTasks object.
   *
   * @param \Drupal\tmgmt\SourceManager $source_manager
   *   The source manager.
   * @param \Drupal\tmgmt\ContinuousManager $continuous_manager
   *   The continuous manager.
   */
  public function __construct(SourceManager $source_manager, ContinuousManager $continuous_manager) {
    $this->sourceManager = $source_manager;
    $this->continuousManager = $continuous_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.tmgmt.source'),
      $container->get('tmgmt.continuous')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin = NULL, $item_type = NULL) {
    // Look for the first source overview local task and make the parent use the
    // same route arguments.
    // @todo: Find a nicer way to get the main source (usually entity:node), also,
    //   this looses the trail because the parent points directly to the first
    //   sub-task. Use a different route that doesn't require the arguments?
    if (!isset($plugin)) {
      $definitions = $this->sourceManager->getDefinitions();
      if (empty($definitions)) {
        return array('#markup' => 'No sources enabled.');
      }
      if (isset($definitions['content'])) {
        $plugin = 'content';
      }
      else {
        $plugin = key($definitions);
      }
    }

    $source = $this->sourceManager->createInstance($plugin);
    if (!isset($item_type)) {
      $item_types = $source->getItemTypes();
      if (empty($item_types)) {
        return array('#markup' => 'No sources enabled.');
      }
      if (isset($item_types['node'])) {
        $item_type = 'node';
      }
      else {
        $item_type = key($item_types);
      }
    }

    $definition = $this->sourceManager->getDefinition($plugin);

    $form['#title'] = $this->t('@type overview (@plugin)', array('@type' => $source->getItemTypeLabel($item_type), '@plugin' => $definition['label']));

    $options = array();
    foreach ($this->sourceManager->getDefinitions() as $plugin_id => $definition) {
      $plugin_type = $this->sourceManager->createInstance($plugin_id);
      $item_types = $plugin_type->getItemTypes();
      asort($item_types);
      foreach ($item_types as $item_type_key => $item_type_label) {
        $options[(string) $definition['label']][$plugin_id . ':' . $item_type_key] = $item_type_label;
      }
    }
    $form['source_type'] = array(
      '#type' => 'container',
      '#open' => TRUE,
      '#attributes' => array('class' => array('tmgmt-source-type-wrapper')),
      '#weight' => -100,
    );
    $form['source_type']['source'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $plugin . ':' . $item_type,
      '#title' => t('Choose source'),
      '#ajax' => array(
        'callback' => array($this, 'ajaxCallback'),
      ),
    );
    $form['source_type']['choose'] = array(
      '#type' => 'submit',
      '#value' => t('Choose'),
      '#submit' => array(
        '::sourceSelectSubmit',
      ),
      '#attributes' => array('class' => array('js-hide')),
    );

    $form['operations'] = [
      '#type' => 'container',
      '#attributes' => array('class' => array('tmgmt-source-operations-wrapper')),
    ];
    $form['operations']['checkout'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Checkout'),
      '#attributes' => array('class' => array('tmgmt-source-checkout-wrapper')),
    );

    $languages = tmgmt_available_languages();

    $form['operations']['checkout']['source_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Source language'),
      '#options' => [static::SOURCE => $this->t('- Original -')] + $languages,
    ];

    $form['operations']['checkout']['target_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Target language'),
      '#empty_option' => $this->t('- Select later -'),
      '#empty_value' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      '#options' => [
        static::MULTIPLE => $this->t('- Multiple -'),
        static::ALL => $this->t('- All -'),
      ] + $languages,
    ];

    $form['operations']['checkout']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#validate' => array('::validateItemsSelected'),
      '#value' => t('Request translation'),
      '#submit' => array('::submitForm'),
    );

    $form['operations']['checkout']['target_languages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Target languages'),
      '#options' => $languages,
      '#states' => [
        'visible' => [
          ':input[name=target_language]' => ['value' => static::MULTIPLE],
        ],
      ],
    ];

    $form['operations']['checkout']['check_target_languages'] = [
      '#type' => 'item',
      '#markup' => '<div class="check-control"><a class="check-all">' . $this->t('Check all / Uncheck All') . '</a></div>',
      '#states' => [
        'visible' => [
          ':input[name=target_language]' => ['value' => static::MULTIPLE],
        ],
      ],
    ];

    $form['operations']['operations'] = array(
      '#type' => 'details',
      '#title' => $this->t('Operations'),
      '#open' => TRUE,
      '#attributes' => array('class' => array('tmgmt-source-additional-wrapper')),
    );
    tmgmt_add_cart_form($form['operations']['operations'], $form_state, $plugin, $item_type);

    if ($source instanceof ContinuousSourceInterface && $this->continuousManager->hasContinuousJobs()) {
      $form['operations']['operations']['add_to_continuous_jobs'] = array(
        '#type' => 'submit',
        '#validate' => array('::validateItemsSelected'),
        '#value' => t('Check for continuous jobs'),
        '#submit' => array('::submitToContinuousJobs'),
      );
      $form['operations']['operations']['add_all_to_continuous_jobs'] = array(
        '#type' => 'checkbox',
        '#title' => 'All (continuous check only)',
        '#default_value' => FALSE,
      );
    }

    $source_ui = $this->sourceManager->createUIInstance($plugin);
    $form_state->set('plugin', $plugin);
    $form_state->set('item_type', $item_type);

    $form = $source_ui->overviewForm($form, $form_state, $item_type);

    $form['legend'] = tmgmt_color_legend();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $plugin = $form_state->get('plugin');
    $item_type = $form_state->get('item_type');
    // Execute the validation method on the source plugin controller.
    $source_ui = $this->sourceManager->createUIInstance($plugin);
    $source_ui->overviewFormValidate($form, $form_state, $item_type);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $plugin =  $form_state->get('plugin');

    $item_type = $form_state->get('item_type');
    // Execute the submit method on the source plugin controller.
    $source_ui = $this->sourceManager->createUIInstance($plugin);
    $source_ui->overviewFormSubmit($form, $form_state, $item_type);
  }

  /**
   * {@inheritdoc}
   */
  public function sourceSelectSubmit(array &$form, FormStateInterface $form_state) {
    // Separate the plugin and source type.
    $source = $form_state->getValue('source');
    // Redirect to the selected source type.
    $form_state->setRedirectUrl($this->getUrlForSource($source));
  }

  /**
   * Submit method for Add to continuous jobs button.
   *
   * @param array $form
   *   Drupal form array.
   * @param FormStateInterface $form_state
   *   Drupal form_state array.
   */
  public function submitToContinuousJobs(array &$form, FormStateInterface $form_state) {
    $plugin = $form_state->get('plugin');
    $item_type = $form_state->get('item_type');
    // Execute the submit method on the source plugin controller.
    $source_ui = $this->sourceManager->createUIInstance($plugin);
    $source_ui->overviewSubmitToContinuousJobs($form_state, $item_type);
  }

  /**
   * AJAX callback to refresh form.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @return array
   *   Form element to replace.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $source = $form_state->getValue('source');

    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($this->getUrlForSource($source)->setAbsolute()->toString()));
    return $response;
  }

  /**
   * Gets the url when selecting a source type.
   *
   * @param string $source
   * @return \Drupal\Core\Url
   */
  public function getUrlForSource($source) {
    list($selected_plugin, $selected_item_type) = explode(':', $source);
    return Url::fromRoute('tmgmt.source_overview', array('plugin' => $selected_plugin, 'item_type' => $selected_item_type));
  }

  /**
   * Validation for selected items.
   *
   * @param array $form
   *   An associate array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateItemsSelected(array $form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#submit'][0] == '::submitToContinuousJobs' && $form_state->getValue('add_all_to_continuous_jobs')) {
      return;
    }
    tmgmt_cart_source_overview_validate($form, $form_state);
  }

}

