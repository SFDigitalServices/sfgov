<?php

namespace Drupal\sfgov_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block for sfgov search form
 *
 * @Block(
 *   id = "sfgov_search_form_block",
 *   admin_label = @Translation("SF Gov Search Block"),
 *   category = @Translation("SF Gov Blocks"),
 * )
 */
class SearchFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new SearchFormBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $languageManager
   *   The language manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $languageManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $languageManager = \Drupal::languageManager();
    $form = \Drupal::formBuilder()->getForm('Drupal\sfgov_search\Form\SearchForm');
    return [
      'form' => $form,
      '#attached' => [
        'drupalSettings' => [
          'sfgov_search_form_block' => [
            'language_prefix' => $languageManager->getCurrentLanguage()->isDefault() ?
              '' :
              '/' . $languageManager->getCurrentLanguage()->getId(),
          ],
        ],
        'library' => [
          'core/drupalSettings',
        ],
      ],
    ];
  }
}
