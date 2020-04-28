<?php

namespace Drupal\fixed_block_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\fixed_block_content\Entity\FixedBlockContent;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;

/**
 * Provides a fixed content block placement.
 *
 * @Block(
 *   id = "fixed_block_content",
 *   admin_label = @Translation("Fixed custom block"),
 *   category = @Translation("Fixed custom"),
 *   deriver = "Drupal\fixed_block_content\Plugin\Derivative\FixedBlockContent"
 * )
 */
class FixedBlockContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a FixedBlockContentBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = [];

    if (($fixed_block = FixedBlockContent::load($this->getDerivativeId()))
      && ($block_content = $fixed_block->getBlockContent())) {
      $output = $this->entityTypeManager
        ->getViewBuilder('block_content')
        ->view($block_content, isset($this->configuration['view_mode']) ? $this->configuration['view_mode'] : '');
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $fixed_block = FixedBlockContent::load($this->getDerivativeId());
    $options = $this->entityDisplayRepository->getViewModeOptionsByBundle('block_content', $fixed_block->getBlockContentBundle());

    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('View mode'),
      '#description' => $this->t('Output the block in this view mode.'),
      '#default_value' => isset($this->configuration['view_mode']) ? $this->configuration['view_mode'] : '',
      '#access' => (count($options) > 1),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    /** @var \Drupal\fixed_block_content\FixedBlockContentInterface $fixed_block */
    if ($fixed_block = FixedBlockContent::load($this->getDerivativeId())) {
      $cache_tags = Cache::mergeTags($cache_tags, $fixed_block->getCacheTags());
      $cache_tags = Cache::mergeTags($cache_tags, $fixed_block->getBlockContent()->getCacheTags());
    }

    return $cache_tags;
  }

}
