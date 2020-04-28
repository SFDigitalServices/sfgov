<?php

namespace Drupal\advagg_bundler\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure advagg bundler settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The AdvAgg cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The AdvAgg cache service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache) {
    parent::__construct($config_factory);
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.advagg')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advagg_bundler_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['advagg_bundler.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('advagg_bundler.settings');
    $form['active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bundler is Active'),
      '#default_value' => $config->get('active'),
      '#description' => $this->t('If not checked, the bundler will not split up aggregates.'),
    ];

    $options = [
      0 => 0,
      1 => 1,
      2 => 2,
      3 => 3,
      4 => 4,
      5 => 5,
      6 => 6,
      7 => 7,
      8 => 8,
      9 => 9,
      10 => 10,
      11 => 11,
      12 => 12,
      13 => 13,
      14 => 14,
      15 => 15,
    ];
    $form['css'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CSS Bundling options.'),
    ];
    $form['css']['max_css'] = [
      '#type' => 'select',
      '#title' => $this->t('Target Number Of CSS Bundles Per Page'),
      '#default_value' => $config->get('css.max'),
      '#options' => $options,
      '#description' => $this->t('If 0 is selected then the bundler is disabled'),
      '#states' => [
        'disabled' => [
          '#edit-active' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['css']['css_logic'] = [
      '#type' => 'radios',
      '#title' => $this->t('Grouping logic'),
      '#default_value' => $config->get('css.logic'),
      '#options' => [
        0 => $this->t('File count'),
        1 => $this->t('File size'),
      ],
      '#description' => $this->t('If file count is selected then each bundle will try to have a similar number of original files aggregated inside of it. If file size is selected then each bundle will try to have a similar file size.'),
      '#states' => [
        'disabled' => [
          '#edit-active' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['js'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('JavaScript Bundling options.'),
    ];
    $form['js']['max_js'] = [
      '#type' => 'select',
      '#title' => $this->t('Target Number Of JS Bundles Per Page'),
      '#default_value' => $config->get('js.max'),
      '#options' => $options,
      '#description' => $this->t('If 0 is selected then the bundler is disabled'),
      '#states' => [
        'disabled' => [
          '#edit-active' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['js']['js_logic'] = [
      '#type' => 'radios',
      '#title' => $this->t('Grouping logic'),
      '#default_value' => $config->get('js.logic'),
      '#options' => [
        0 => $this->t('File count'),
        1 => $this->t('File size'),
      ],
      '#description' => $this->t('If file count is selected then each bundle will try to have a similar number of original files aggregated inside of it. If file size is selected then each bundle will try to have a similar file size.'),
      '#states' => [
        'disabled' => [
          '#edit-active' => ['checked' => FALSE],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('advagg_bundler.settings')
      ->set('active', $form_state->getValue('active'))
      ->set('css.max', $form_state->getValue('max_css'))
      ->set('css.logic', $form_state->getValue('css_logic'))
      ->set('js.max', $form_state->getValue('max_js'))
      ->set('js.logic', $form_state->getValue('js_logic'));
    $config->save();

    // Clear Caches.
    Cache::invalidateTags(['library_info']);
    $this->cache->invalidateAll();

    parent::submitForm($form, $form_state);
  }

}
