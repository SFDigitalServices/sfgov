<?php

namespace Drupal\simple_instagram_feed\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_instagram_feed\Services\SimpleInstagramFeedLibraryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with a dynamic Instagram Feed.
 *
 * @Block(
 *   id = "simple_instagram_block",
 *   admin_label = @Translation("Simple Instagram Feed"),
 * )
 */
class SimpleInstagramBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The simple instagram feed library service.
   *
   * @var \Drupal\simple_instagram_feed\Services\SimpleInstagramFeedLibraryInterface
   */
  private $simpleInstagramFeedLibrary;

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
        'items' => 12,
        'styling' => 'true',
        'captions' => false,
        'instagram_username' => 'instagram',
        'display_profile' => true,
        'display_biography' => true,
        'items_per_row_type' => false,
        'items_per_row_default' => 5,
        'items_per_row_l_720' => 5,
        'items_per_row_l_960' => 5,
        'items_per_row_h_960' => 5,
        'lazy_load' => true,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['simple_instagram_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instagram username'),
      '#description' => $this->t('Insert the username of the instagram account in the field above.'),
      '#default_value' => isset($config['simple_instagram_username']) ? $config['simple_instagram_username'] : 'instagram',
      '#required' => TRUE,
    ];

    $form['simple_instagram_display_profile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display profile?'),
      '#description' => $this->t('Do you wish to display the Instagram profile on this Instagram Feed?'),
      '#default_value' => isset($config['simple_instagram_display_profile']) ? $config['simple_instagram_display_profile'] : 'true',
    ];

    $form['simple_instagram_display_biography'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display bio?'),
      '#description' => $this->t('Do you wish to display the Instagram Bio on this Instagram Feed?'),
      '#default_value' => isset($config['simple_instagram_display_biography']) ? $config['simple_instagram_display_biography'] : 'true',
    ];

    $form['simple_instagram_captions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display captions'),
      '#description' => $this->t('Enables displaying captions for each post as overlay on hover.'),
      '#default_value' => isset($config['simple_instagram_captions']) ? $config['simple_instagram_captions'] : FALSE,
      '#attributes' => [
        'id' => 'simple_instagram_captions',
      ],
    ];

    $form['simple_instagram_items'] = [
      '#type' => 'textfield',
      '#size' => 3,
      '#maxlength' => 3,
      '#title' => $this->t('Number of images'),
      '#description' => $this->t('How many images do you wish to feature on this Instagram Feed?'),
      '#default_value' => isset($config['simple_instagram_items']) ? $config['simple_instagram_items'] : '12',
      '#required' => TRUE,
    ];

    $form['simple_instagram_items_per_row'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Items per row'),
    ];

    $form['simple_instagram_items_per_row']['simple_instagram_items_per_row_type'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check it if you want to choose how many images to display depends on the window size.'),
      '#default_value' => isset($config['simple_instagram_items_per_row_type']) ? $config['simple_instagram_items_per_row_type'] : 0,
      '#attributes' => [
        'id' => 'simple_instagram_items_per_row_type',
      ],
    ];

    $simple_items_range = range(1, 12);
    $form['simple_instagram_items_per_row']['simple_instagram_items_per_row_default'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of images per row for all window size.'),
      '#description' => $this->t('How many images do you wish to feature on each row of this Instagram Feed? You can produce a single row if you set the number of images to equal the number of images per row.'),
      '#options' => [$simple_items_range],
      '#default_value' => isset($config['simple_instagram_items_per_row_default']) ? $config['simple_instagram_items_per_row_default'] : '5',
      '#states' => [
        'visible' => [
          ':input[id="simple_instagram_items_per_row_type"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['simple_instagram_items_per_row']['simple_instagram_items_per_row_l_720'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of images per row if window size is <720px.'),
      '#description' => $this->t('How many images do you wish on each row if the user window size is lower than 720px.'),
      '#options' => [$simple_items_range],
      '#default_value' => isset($config['simple_instagram_items_per_row_l_720']) ? $config['simple_instagram_items_per_row_l_720'] : '5',
      '#states' => [
        'visible' => [
          ':input[id="simple_instagram_items_per_row_type"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['simple_instagram_items_per_row']['simple_instagram_items_per_row_l_960'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of images per row if window size is >= 720px and < 960.'),
      '#description' => $this->t('How many images do you wish on each row if the user window size is lower than 960px and higher than 720px.'),
      '#options' => [$simple_items_range],
      '#default_value' => isset($config['simple_instagram_items_per_row_l_960']) ? $config['simple_instagram_items_per_row_l_960'] : '5',
       '#states' => [
         'visible' => [
          ':input[id="simple_instagram_items_per_row_type"]' => ['checked' => TRUE],
         ],
       ],
    ];

    $form['simple_instagram_items_per_row']['simple_instagram_items_per_row_h_960'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of images per row if window size is >=960px.'),
      '#description' => $this->t('How many images do you wish on each row if the user window size is higher than 960px.'),
      '#options' => [$simple_items_range],
      '#default_value' => isset($config['simple_instagram_items_per_row_h_960']) ? $config['simple_instagram_items_per_row_h_960'] : '5',
       '#states' => [
        'visible' => [
          ':input[id="simple_instagram_items_per_row_type"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['simple_instagram_styling'] = [
      '#type' => 'select',
      '#options' => ['true' => 'True', 'false' => 'False'],
      '#title' => $this->t('Styling'),
      '#description' => $this->t('Uncheck to omit instagramFeed styles and provide your own in your CSS. Enabled by default when using capions.'),
      '#default_value' => isset($config['simple_instagram_styling']) ? $config['simple_instagram_styling'] : 'true',
      '#states' => [
        'disabled' => [
          ':input[id="simple_instagram_captions"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => isset($config['simple_instagram_styling']) ?? 'true',
    ];

    $simple_image_sizes = [
      '640' => 640,
      '480' => 480,
      '320' => 320,
      '240' => 240,
      '150' => 150,
    ];

    $form['simple_instagram_image_size'] = [
      '#type' => 'select',
      '#options' => $simple_image_sizes,
      '#title' => $this->t('Image Size'),
      '#description' => $this->t('Scale of items to build gallery. Accepted values [150, 240, 320, 480, 640].'),
      '#default_value' => isset($config['simple_instagram_image_size']) ? $config['simple_instagram_image_size'] : '640',
    ];

    $form['simple_instagram_lazy_load'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Lazyload assets'),
      '#description' => $this->t('Do you wish to Lazy-load on this Instagram Feed?'),
      '#default_value' => isset($config['simple_instagram_lazy_load']) ? $config['simple_instagram_lazy_load'] : 'true',
    ];

    // Add a warning if the js library is not available.
    $this->simpleInstagramFeedLibrary->isAvailable(TRUE);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['simple_instagram_username'] = $values['simple_instagram_username'];
    $this->configuration['simple_instagram_display_profile'] = $values['simple_instagram_display_profile'];
    $this->configuration['simple_instagram_display_biography'] = $values['simple_instagram_display_biography'];
    $this->configuration['simple_instagram_captions'] = $values['simple_instagram_captions'];
    $this->configuration['simple_instagram_items'] = $values['simple_instagram_items'];
    $this->configuration['simple_instagram_items_per_row_type'] = $values['simple_instagram_items_per_row']['simple_instagram_items_per_row_type'];
    $this->configuration['simple_instagram_items_per_row_default'] = $values['simple_instagram_items_per_row']['simple_instagram_items_per_row_default'];
    $this->configuration['simple_instagram_items_per_row_l_720'] = $values['simple_instagram_items_per_row']['simple_instagram_items_per_row_l_720'];
    $this->configuration['simple_instagram_items_per_row_l_960'] = $values['simple_instagram_items_per_row']['simple_instagram_items_per_row_l_960'];
    $this->configuration['simple_instagram_items_per_row_h_960'] = $values['simple_instagram_items_per_row']['simple_instagram_items_per_row_h_960'];
    $this->configuration['simple_instagram_styling'] = $values['simple_instagram_styling'];
    $this->configuration['simple_instagram_image_size'] = $values['simple_instagram_image_size'];
    $this->configuration['simple_instagram_lazy_load'] = $values['simple_instagram_lazy_load'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!$this->simpleInstagramFeedLibrary->isAvailable()) {
      return [];
    }
    $unique_id = Html::getUniqueId($this->getPluginId());

    $build = [
      '#unique_id' => $unique_id,
      '#theme' => 'simple_instagram_block',
      '#markup' => $this->t('Simple Instagram Feed'),
      '#attached' => [
        'library' => ['simple_instagram_feed/simple_instagram_block'],
        'drupalSettings' => []
      ],
      '#cache' => [
        'max-age' => 3600,
      ],
    ];
    $build['#attached']['drupalSettings']['simple_instagram_feed'][$unique_id] = $this->buildAttachedSettings();
    $build['#attached']['drupalSettings']['simple_instagram_feed'][$unique_id]['unique_id'] = $unique_id;

    return $build;
  }

  /**
   * Build instagram attached settings.
   *
   * @return array
   *   An array of the formatted settings.
   */
  protected function buildAttachedSettings() {
    $config = $this->getConfiguration();

    return [
      'items' => $config['simple_instagram_items'],
      'styling' => $config['simple_instagram_styling'],
      'captions' => $config['simple_instagram_captions'],
      'image_size' => $config['simple_instagram_image_size'],
      'instagram_username' => $config['simple_instagram_username'],
      'display_profile' => $config['simple_instagram_display_profile'],
      'display_biography' => $config['simple_instagram_display_biography'],
      'items_per_row_type' => $config['simple_instagram_items_per_row_type'],
      'items_per_row_default' => $config['simple_instagram_items_per_row_default'] + 1,
      'items_per_row_l_720' => $config['simple_instagram_items_per_row_l_720'] + 1,
      'items_per_row_l_960' => $config['simple_instagram_items_per_row_l_960'] + 1,
      'items_per_row_h_960' => $config['simple_instagram_items_per_row_h_960'] + 1,
      'lazy_load' => $config['simple_instagram_lazy_load'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    SimpleInstagramFeedLibraryInterface $simple_instagram_feed_library
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->simpleInstagramFeedLibrary = $simple_instagram_feed_library;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_instagram_feed.library')
    );
  }

}
