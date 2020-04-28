<?php

namespace Drupal\simple_sitemap_views\Plugin\views\display_extender;

use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Plugin\views\display\DisplayRouterInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\simple_sitemap\SimplesitemapManager;
use Drupal\simple_sitemap\Form\FormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;

/**
 * Simple XML Sitemap display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "simple_sitemap_display_extender",
 *   title = @Translation("Simple XML Sitemap"),
 *   help = @Translation("Simple XML Sitemap settings for this view."),
 *   no_ui = FALSE
 * )
 */
class SimpleSitemapDisplayExtender extends DisplayExtenderPluginBase {

  /**
   * Simple XML Sitemap form helper.
   *
   * @var \Drupal\simple_sitemap\Form\FormHelper
   */
  protected $formHelper;

  /**
   * Simple XML Sitemap manager.
   *
   * @var \Drupal\simple_sitemap\SimplesitemapManager
   */
  protected $sitemapManager;

  /**
   * Constructs the plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\simple_sitemap\Form\FormHelper $form_helper
   *   Simple XML Sitemap form helper.
   * @param \Drupal\simple_sitemap\SimplesitemapManager $sitemap_manager
   *   Simple XML Sitemap manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormHelper $form_helper, SimplesitemapManager $sitemap_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formHelper = $form_helper;
    $this->sitemapManager = $sitemap_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.form_helper'),
      $container->get('simple_sitemap.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    if (!$this->hasSitemapSettings()) {
      $this->options = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['index'] = ['default' => 0];
    $options['variant'] = ['default' => NULL];
    $options['priority'] = ['default' => 0.5];
    $options['changefreq'] = ['default' => ''];
    $options['arguments'] = ['default' => []];
    $options['max_links'] = ['default' => 100];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    if ($this->hasSitemapSettings() && $form_state->get('section') == 'simple_sitemap') {
      $form['#title'] .= $this->t('Simple XML Sitemap settings for this display');
      $settings = $this->getSitemapSettings();

      // The index section.
      $form['index'] = [
        '#prefix' => '<div class="simple-sitemap-views-index">',
        '#suffix' => '</div>',
      ];
      // Add a checkbox for JS users, which will have behavior attached to it
      // so it can replace the button.
      $form['index']['index'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Index this display'),
        '#default_value' => $settings['index'],
      ];
      // Then add the button itself.
      $form['index']['index_button'] = [
        '#limit_validation_errors' => [],
        '#type' => 'submit',
        '#value' => $this->t('Index this display'),
        '#submit' => [[$this, 'displaySitemapSettingsForm']],
      ];

      // Show the whole form only if indexing is checked.
      if ($this->options['index']) {
        // Main settings fieldset.
        $form['main'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Main settings'),
        ];
        // The sitemap variant.
        $form['main']['variant'] = [
          '#type' => 'select',
          '#title' => $this->t('Sitemap variant'),
          '#description' => $this->t('The sitemap variant this display is to be indexed in.'),
          '#options' => $this->formHelper->getVariantSelectValues(),
          '#default_value' => $this->formHelper->getVariantSelectValuesDefault($settings['variant']),
          '#required' => TRUE,
        ];
        // The sitemap priority.
        $form['main']['priority'] = [
          '#type' => 'select',
          '#title' => $this->t('Priority'),
          '#description' => $this->t('The priority this display will have in the eyes of search engine bots.'),
          '#default_value' => $settings['priority'],
          '#options' => $this->formHelper->getPrioritySelectValues(),
        ];
        // The sitemap change frequency.
        $form['main']['changefreq'] = [
          '#type' => 'select',
          '#title' => $this->t('Change frequency'),
          '#description' => $this->t('The frequency with which this display changes. Search engine bots may take this as an indication of how often to index it.'),
          '#default_value' => $settings['changefreq'],
          '#options' => $this->formHelper->getChangefreqSelectValues(),
        ];

        // Argument settings fieldset.
        $form['arguments'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Argument settings'),
        ];
        // Get view arguments options.
        if ($arguments_options = $this->getArgumentsOptions()) {
          // Indexed arguments element.
          $form['arguments']['arguments'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Indexed arguments'),
            '#options' => $arguments_options,
            '#default_value' => $settings['arguments'],
            '#attributes' => ['class' => ['indexed-arguments']],
          ];
          // Max links with arguments.
          $form['arguments']['max_links'] = [
            '#type' => 'number',
            '#title' => $this->t('Maximum display variations'),
            '#description' => $this->t('The maximum number of link variations to be indexed for this display. If left blank, each argument will create link variations for this display. Use with caution, as a large number of argument values​can significantly increase the number of sitemap links.'),
            '#default_value' => $settings['max_links'],
            '#min' => 1,
          ];
        }
        else {
          $form['arguments']['#description'] = $this->t('This display has no arguments.');
        }
      }

      // Attaching script to form.
      $form['#attached']['library'][] = 'simple_sitemap_views/viewsUi';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    if ($this->hasSitemapSettings() && $form_state->get('section') == 'simple_sitemap') {
      // Validate indexed arguments.
      $arguments = $form_state->getValue('arguments', []);
      $errors = $this->validateIndexedArguments($arguments);
      foreach ($errors as $message) {
        $form_state->setError($form['arguments']['arguments'], $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    if ($this->hasSitemapSettings() && $form_state->get('section') == 'simple_sitemap') {
      $values = $form_state->cleanValues()->getValues();
      $values['arguments'] = isset($values['arguments']) ? array_filter($values['arguments']) : [];
      // Save sitemap settings.
      foreach ($values as $key => $value) {
        if (array_key_exists($key, $this->options)) {
          $this->options[$key] = $value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();

    // Validate the argument options relative to the
    // current state of the view argument handlers.
    if ($this->hasSitemapSettings()) {
      $settings = $this->getSitemapSettings();
      $result = $this->validateIndexedArguments($settings['arguments']);
      $errors = array_merge($errors, $result);
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    if ($this->hasSitemapSettings()) {
      $categories['simple_sitemap'] = [
        'title' => $this->t('Simple XML Sitemap'),
        'column' => 'second',
      ];
      $options['simple_sitemap'] = [
        'category' => 'simple_sitemap',
        'title' => $this->t('Status'),
        'value' => $this->isIndexingEnabled() ? $this->t('Included in sitemap') : $this->t('Excluded from sitemap'),
      ];
    }
  }

  /**
   * Displays the sitemap settings form.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function displaySitemapSettingsForm(array $form, FormStateInterface $form_state) {
    // Update index option.
    $this->options['index'] = empty($this->options['index']);

    // Rebuild settings form.
    /** @var \Drupal\views_ui\ViewUI $view */
    $view = $form_state->get('view');
    $display_handler = $view->getExecutable()->display_handler;
    $extender_options = $display_handler->getOption('display_extenders');
    if (isset($extender_options[$this->pluginId])) {
      $extender_options[$this->pluginId] = $this->options;
      $display_handler->setOption('display_extenders', $extender_options);
    }
    $view->cacheSet();
    $form_state->set('rerender', TRUE);
    $form_state->setRebuild();
  }

  /**
   * Get sitemap settings configuration for this display.
   *
   * @return array
   *   The sitemap settings.
   */
  public function getSitemapSettings() {
    return $this->options;
  }

  /**
   * Identify whether or not the current display has sitemap settings.
   *
   * @return bool
   *   Has sitemap settings (TRUE) or not (FALSE).
   */
  public function hasSitemapSettings() {
    return $this->displayHandler instanceof DisplayRouterInterface;
  }

  /**
   * Identify whether or not the current display indexing is enabled.
   *
   * @return bool
   *   Indexing is enabled (TRUE) or not (FALSE).
   */
  public function isIndexingEnabled() {
    $settings = $this->getSitemapSettings();
    return !empty($settings['index']);
  }

  /**
   * Returns available view arguments options.
   *
   * @return array
   *   View arguments labels keyed by argument ID.
   */
  protected function getArgumentsOptions() {
    $arguments_options = [];
    // Get view argument handlers.
    $arguments = $this->displayHandler->getHandlers('argument');
    /** @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument */
    foreach ($arguments as $id => $argument) {
      $arguments_options[$id] = $argument->adminLabel();
    }
    return $arguments_options;
  }

  /**
   * Validate indexed arguments.
   *
   * @param array $indexed_arguments
   *   Indexed arguments array.
   *
   * @return array
   *   An array of error strings. This will be empty if there are no validation
   *   errors.
   */
  protected function validateIndexedArguments(array $indexed_arguments) {
    $arguments = $this->displayHandler->getHandlers('argument');
    $arguments = array_fill_keys(array_keys($arguments), 0);
    $arguments = array_merge($arguments, $indexed_arguments);
    reset($arguments);

    $errors = [];
    while (($argument = current($arguments)) !== FALSE) {
      $next_argument = next($arguments);
      if (empty($argument) && !empty($next_argument)) {
        $errors[] = $this->t('To enable indexing of an argument, you must enable indexing of all previous arguments.');
        break;
      }
    }
    return $errors;
  }

}
