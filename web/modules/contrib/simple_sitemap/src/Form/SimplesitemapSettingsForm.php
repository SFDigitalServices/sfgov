<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Datetime\DateFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Database\Connection;

/**
 * Class SimplesitemapSettingsForm
 * @package Drupal\simple_sitemap\Form
 */
class SimplesitemapSettingsForm extends SimplesitemapFormBase {

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * SimplesitemapSettingsForm constructor.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\Form\FormHelper $form_helper
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   */
  public function __construct(
    Simplesitemap $generator,
    FormHelper $form_helper,
    LanguageManager $language_manager,
    Connection $database,
    DateFormatter $date_formatter
  ) {
    parent::__construct(
      $generator,
      $form_helper
    );
    $this->languageManager = $language_manager;
    $this->db = $database;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.form_helper'),
      $container->get('language_manager'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_sitemap_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['simple_sitemap_settings']['#prefix'] = FormHelper::getDonationText();
    $form['simple_sitemap_settings']['#attached']['library'][] = 'simple_sitemap/sitemapSettings';
    $queue_worker = $this->generator->getQueueWorker();

    $form['simple_sitemap_settings']['status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sitemap status'),
      '#markup' => '<div class="description">' . $this->t('Sitemaps can be regenerated on demand here.') . '</div>',
      '#description' => $this->t('Variants can be configured <a href="@url">here</a>.', ['@url' => $GLOBALS['base_url'] . '/admin/config/search/simplesitemap/variants']),
    ];

    $form['simple_sitemap_settings']['status']['actions'] = [
      '#prefix' => '<div class="clearfix"><div class="form-item">',
      '#suffix' => '</div></div>',
    ];

    $form['simple_sitemap_settings']['status']['actions']['rebuild_queue_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rebuild queue'),
      '#submit' => ['::rebuildQueue'],
      '#validate' => [],
    ];

    $form['simple_sitemap_settings']['status']['actions']['regenerate_submit'] = [
      '#type' => 'submit',
      '#value' => $queue_worker->generationInProgress()
        ? $this->t('Resume generation')
        : $this->t('Rebuild queue & generate'),
      '#submit' => ['::generateSitemap'],
      '#validate' => [],
    ];

    $form['simple_sitemap_settings']['status']['progress'] = [
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>',
    ];

    $form['simple_sitemap_settings']['status']['progress']['title']['#markup'] = $this->t('Progress of sitemap regeneration');

    $total_count = $queue_worker->getInitialElementCount();
    if (!empty($total_count)) {
      $indexed_count = $queue_worker->getProcessedElementCount();
      $percent = round(100 * $indexed_count / $total_count);

      // With all results processed, there still may be some stashed results to be indexed.
      $percent = $percent === 100 && $queue_worker->generationInProgress() ? 99 : $percent;

      $index_progress = [
        '#theme' => 'progress_bar',
        '#percent' => $percent,
        '#message' => $this->t('@indexed out of @total items have been processed.<br>Each sitemap variant is published after all of its items have been processed.', ['@indexed' => $indexed_count, '@total' => $total_count]),
      ];
      $form['simple_sitemap_settings']['status']['progress']['bar']['#markup'] = render($index_progress);
    }
    else {
      $form['simple_sitemap_settings']['status']['progress']['bar']['#markup'] = '<div class="description">' . $this->t('There are no items to be indexed.') . '</div>';
    }

    $sitemap_manager = $this->generator->getSitemapManager();
    $sitemap_settings = [
      'base_url' => $this->generator->getSetting('base_url', ''),
      'default_variant' => $this->generator->getSetting('default_variant', NULL),
    ];
    $sitemap_statuses = $this->fetchSitemapInstanceStatuses();
    $published_timestamps = $this->fetchSitemapInstancePublishedTimestamps();
    foreach ($sitemap_manager->getSitemapTypes() as $type_name => $type_definition) {
      if (!empty($variants = $sitemap_manager->getSitemapVariants($type_name, FALSE))) {
        $sitemap_generator = $sitemap_manager
          ->getSitemapGenerator($type_definition['sitemapGenerator'])
          ->setSettings($sitemap_settings);

        $form['simple_sitemap_settings']['status']['types'][$type_name] = [
          '#type' => 'details',
          '#title' => '<em>' . $type_definition['label'] . '</em> ' . $this->t('sitemaps'),
          '#open' => !empty($variants) && count($variants) <= 5,
          '#description' => !empty($type_definition['description']) ? '<div class="description">' . $type_definition['description'] . '</div>' : '',
        ];
        $form['simple_sitemap_settings']['status']['types'][$type_name]['table'] = [
          '#type' => 'table',
          '#header' => [$this->t('Variant'), $this->t('Status')],
          '#attributes' => ['class' => ['form-item', 'clearfix']],
        ];
        foreach ($variants as $variant_name => $variant_definition) {
          $row = [];
          $row['name']['data']['#markup'] = '<span title="' . $variant_name . '">' . $this->t($variant_definition['label']) . '</span>';
          if (!isset($sitemap_statuses[$variant_name])) {
            $row['status'] = $this->t('pending');
          }
          else {
            switch ($sitemap_statuses[$variant_name]) {
              case 0:
                $row['status'] = $this->t('generating');
                break;
              case 1:
                $row['status']['data']['#markup'] = $this->t('<a href="@url" target="_blank">published on @time</a>',
                  ['@url' => $sitemap_generator->setSitemapVariant($variant_name)->getSitemapUrl(), '@time' => $this->dateFormatter->format($published_timestamps[$variant_name])]
                );
                break;
              case 2:
                $row['status'] = $this->t('<a href="@url" target="_blank">published on @time</a>, regenerating',
                  ['@url' => $sitemap_generator->setSitemapVariant($variant_name)->getSitemapUrl(), '@time' => $this->dateFormatter->format($published_timestamps[$variant_name])]
                );
                break;
            }
          }
          $form['simple_sitemap_settings']['status']['types'][$type_name]['table']['#rows'][$variant_name] = $row;
          unset($sitemap_statuses[$variant_name]);
        }
      }
    }
    if (empty($form['simple_sitemap_settings']['status']['types'])) {
      $form['simple_sitemap_settings']['status']['types']['#markup'] = $this->t('No variants have been defined');
    }

/*    if (!empty($sitemap_statuses)) {
      $form['simple_sitemap_settings']['status']['types']['&orphans'] = [
        '#type' => 'details',
        '#title' => $this->t('Orphans'),
        '#open' => TRUE,
      ];

      $form['simple_sitemap_settings']['status']['types']['&orphans']['table'] = [
        '#type' => 'table',
        '#header' => [$this->t('Variant'), $this->t('Status'), $this->t('Actions')],
      ];
      foreach ($sitemap_statuses as $orphan_name => $orphan_info) {
        $form['simple_sitemap_settings']['status']['types']['&orphans']['table']['#rows'][$orphan_name] = [
          'name' => $orphan_name,
          'status' => $this->t('orphaned'),
          'actions' => '',
        ];
      }
    }*/

    $form['simple_sitemap_settings']['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];

    $form['simple_sitemap_settings']['settings']['cron_generate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Regenerate the sitemaps during cron runs'),
      '#description' => $this->t('Uncheck this if you intend to only regenerate the sitemaps manually or via drush.'),
      '#default_value' => $this->generator->getSetting('cron_generate', TRUE),
    ];

    $form['simple_sitemap_settings']['settings']['cron_generate_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Sitemap generation interval'),
      '#description' => $this->t('The sitemap will be generated according to this interval.'),
      '#default_value' => $this->generator->getSetting('cron_generate_interval', 0),
      '#options' => FormHelper::getCronIntervalOptions(),
      '#states' => [
        'visible' => [':input[name="cron_generate"]' => ['checked' => TRUE]],
      ],
    ];

    $form['simple_sitemap_settings']['settings']['xsl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add styling and sorting to sitemaps'),
      '#description' => $this->t('If checked, sitemaps will be displayed as tables with sortable entries and thus become much friendlier towards human visitors. Search engines will not care.'),
      '#default_value' => $this->generator->getSetting('xsl', TRUE),
    ];

    $form['simple_sitemap_settings']['settings']['languages'] = [
      '#type' => 'details',
      '#title' => $this->t('Language settings'),
      '#open' => FALSE,
    ];

    $form['simple_sitemap_settings']['settings']['languages']['skip_untranslated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip non-existent translations'),
      '#description' => $this->t('If checked, entity links are generated exclusively for languages the entity has been translated to as long as the language is not excluded below.<br>Otherwise entity links are generated for every language installed on the site apart from languages excluded below.<br>Bear in mind that non-entity paths like homepage will always be generated for every non-excluded language.'),
      '#default_value' => $this->generator->getSetting('skip_untranslated', FALSE),
    ];

    $language_options = [];
    foreach ($this->languageManager->getLanguages() as $language) {
      if (!$language->isDefault()) {
        $language_options[$language->getId()] = $language->getName();
      }
    }
    $form['simple_sitemap_settings']['settings']['languages']['excluded_languages'] = [
      '#title' => $this->t('Exclude languages'),
      '#type' => 'checkboxes',
      '#options' => $language_options,
      '#description' => !empty($language_options)
        ? $this->t('There will be no links generated for languages checked here.')
        : $this->t('There are no languages other than the default language <a href="@url">available</a>.', ['@url' => $GLOBALS['base_url'] . '/admin/config/regional/language']),
      '#default_value' => $this->generator->getSetting('excluded_languages', []),
    ];

    $form['simple_sitemap_settings']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => TRUE,
    ];

    $variants = $this->generator->getSitemapManager()->getSitemapVariants(NULL, FALSE);
    $default_variant = $this->generator->getSetting('default_variant');
    $form['simple_sitemap_settings']['advanced']['default_variant'] = [
      '#type' => 'select',
      '#title' => $this->t('Default sitemap variant'),
      '#description' => $this->t('This sitemap variant will be available under <em>/sitemap.xml</em> in addition to its default path <em>/variant-name/sitemap.xml</em>.<br>Variants can be configured <a href="@url">here</a>.', ['@url' => $GLOBALS['base_url'] . '/admin/config/search/simplesitemap/variants']),
      '#default_value' => isset($variants[$default_variant]) ? $default_variant : '',
      '#options' => ['' => $this->t('- None -')] + array_map(function($variant) { return $this->t($variant['label']); }, $variants),
      ];

    $form['simple_sitemap_settings']['advanced']['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default base URL'),
      '#default_value' => $this->generator->getSetting('base_url', ''),
      '#size' => 30,
      '#description' => $this->t('On some hosting providers it is impossible to pass parameters to cron to tell Drupal which URL to bootstrap with. In this case the base URL of sitemap links can be overridden here.<br>Example: <em>@url</em>', ['@url' => $GLOBALS['base_url']]),
    ];

    $form['simple_sitemap_settings']['advanced']['remove_duplicates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude duplicate links'),
      '#description' => $this->t('Prevent per-sitemap variant duplicate links.<br>Unchecking this may help avoiding PHP memory errors on huge sites.'),
      '#default_value' => $this->generator->getSetting('remove_duplicates', TRUE),
    ];

    $form['simple_sitemap_settings']['advanced']['max_links'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum links in a sitemap'),
      '#min' => 1,
      '#description' => $this->t('The maximum number of links one sitemap can hold. If more links are generated than set here, a sitemap index will be created and the links split into several sub-sitemaps.<br>50 000 links is the maximum Google will parse per sitemap, but choosing a lower value may be needed to avoid PHP memory errors on huge sites.<br>If left blank, all links will be shown on a single sitemap.'),
      '#default_value' => $this->generator->getSetting('max_links'),
    ];

    $form['simple_sitemap_settings']['advanced']['generate_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Sitemap generation max duration'),
      '#min' => 1,
      '#description' => $this->t('The maximum duration <strong>in seconds</strong> the generation task can run during a single cron run or during one batch process iteration.<br>The higher the number, the quicker the generation process, but higher the risk of PHP timeout errors.'),
      '#default_value' => $this->generator->getSetting('generate_duration', 10000) / 1000,
      '#required' => TRUE,
    ];

    $this->formHelper->displayRegenerateNow($form['simple_sitemap_settings']);

    return parent::buildForm($form, $form_state);
  }

  /**
   * @return array
   *  Array of sitemap statuses keyed by variant name.
   *  Status values:
   *  0: Instance is unpublished
   *  1: Instance is published
   *  2: Instance is published but is being regenerated
   *
   * @todo Move to SitemapGeneratorBase or DefaultSitemapGenerator so it can be overwritten by sitemap types with custom storages.
   */
  protected function fetchSitemapInstanceStatuses() {
    $results = $this->db
      ->query('SELECT type, status FROM {simple_sitemap} GROUP BY type, status')
      ->fetchAll();

    $instances = [];
    foreach ($results as $i => $result) {
      $instances[$result->type] = isset($instances[$result->type])
        ? $result->status + 1
        : (int) $result->status;
    }

    return $instances;
  }

  /**
   * @return array
   *
   * @todo Move to SitemapGeneratorBase or DefaultSitemapGenerator so it can be overwritten by sitemap types with custom storages.
   */
  protected function fetchSitemapInstancePublishedTimestamps() {
    return $this->db
      ->query('SELECT type, MAX(sitemap_created) FROM (SELECT sitemap_created, type FROM {simple_sitemap} WHERE status = :status) AS timestamps GROUP BY type', [':status' => 1])
      ->fetchAllKeyed(0, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $base_url = $form_state->getValue('base_url');
    $form_state->setValue('base_url', rtrim($base_url, '/'));
    if ($base_url !== '' && !UrlHelper::isValid($base_url, TRUE)) {
      $form_state->setErrorByName('base_url', $this->t('The base URL is invalid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach (['max_links',
               'cron_generate',
               'cron_generate_interval',
               'remove_duplicates',
               'skip_untranslated',
               'xsl',
               'base_url',
               'default_variant'] as $setting_name) {
      $this->generator->saveSetting($setting_name, $form_state->getValue($setting_name));
    }
    $this->generator->saveSetting('excluded_languages', array_filter($form_state->getValue('excluded_languages')));
    $this->generator->saveSetting('generate_duration', $form_state->getValue('generate_duration') * 1000);

    parent::submitForm($form, $form_state);

    // Regenerate sitemaps according to user setting.
    if ($form_state->getValue('simple_sitemap_regenerate_now')) {
      $this->generator->setVariants(TRUE)
        ->rebuildQueue()
        ->generateSitemap();
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function generateSitemap(array &$form, FormStateInterface $form_state) {
    $this->generator->generateSitemap();
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function rebuildQueue(array &$form, FormStateInterface $form_state) {
    $this->generator->rebuildQueue();
  }

}
