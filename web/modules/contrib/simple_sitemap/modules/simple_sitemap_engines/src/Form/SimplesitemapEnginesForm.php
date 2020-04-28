<?php

namespace Drupal\simple_sitemap_engines\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Form\FormHelper;
use Drupal\simple_sitemap\SimplesitemapManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for managing search engine submission settings.
 */
class SimplesitemapEnginesForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The sitemap manager service.
   *
   * @var \Drupal\simple_sitemap\SimplesitemapManager
   */
  protected $sitemapManager;

  /**
   * SimplesitemapEnginesForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\simple_sitemap\SimplesitemapManager $sitemap_manager
   *   The sitemap manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, DateFormatter $date_formatter, SimplesitemapManager $sitemap_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->sitemapManager = $sitemap_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('simple_sitemap.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_sitemap_engines_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_sitemap_engines.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_sitemap_engines.settings');
    $engines = $this->entityTypeManager->getStorage('simple_sitemap_engine')->loadMultiple();
    $variants = array_map(
      function ($variant) { return $this->t($variant['label']); },
      $this->sitemapManager->getSitemapVariants(NULL, FALSE)
    );

    $form['#tree'] = TRUE;
    $form['engines'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search engines'),
      '#markup' => '<div class="description">' . $this->t('Configure sitemap variants to submit to search engines.') . '</div>',
      '#prefix' => FormHelper::getDonationText(),
    ];
    foreach ($engines as $engine_id => $engine) {
      $form['engines'][$engine_id] = [
        '#type' => 'details',
        '#title' => $engine->label(),
        '#open' => !empty($engine->sitemap_variants) || count($engines) == 1,
      ];
      $form['engines'][$engine_id]['variants'] = [
        '#type' => 'select',
        '#title' => $this->t('Sitemap variants'),
        '#options' => $variants,
        '#default_value' => $engine->sitemap_variants,
        '#multiple' => TRUE,
      ];
    }

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Submission settings'),
    ];
    $form['settings']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Submit the sitemap to search engines'),
      '#default_value' => $config->get('enabled'),
    ];
    $form['settings']['submission_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Submission interval'),
      '#options' => FormHelper::getCronIntervalOptions(),
      '#default_value' => $config->get('submission_interval'),
      '#states' => [
        'visible' => [':input[name="enabled"]' => ['checked' => TRUE]],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $engines = $this->entityTypeManager->getStorage('simple_sitemap_engine')->loadMultiple();
    foreach ($engines as $engine_id => $engine) {
      $engine->sitemap_variants = $form_state->getValue(['engines', $engine_id, 'variants']);
      $engine->save();
    }

    $config = $this->config('simple_sitemap_engines.settings');
    $config->set('enabled', $form_state->getValue(['settings', 'enabled']));
    $config->set('submission_interval', $form_state->getValue(['settings', 'submission_interval']));
    $config->save();
  }

}
