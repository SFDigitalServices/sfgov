<?php

namespace Drupal\color_field\Plugin\Field\FieldFormatter;

use Drupal\color_field\ColorHex;
use Drupal\color_field\Plugin\Field\FieldType\ColorFieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the color_field css declaration formatter.
 *
 * @FieldFormatter(
 *   id = "color_field_formatter_css",
 *   module = "color_field",
 *   label = @Translation("Color CSS declaration"),
 *   field_types = {
 *     "color_field_type"
 *   }
 * )
 */
class ColorFieldFormatterCss extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * The token service.
   *
   * @var \Drupal\token\TokenInterface
   */
  protected $tokenService;

  /**
   * Constructs an ColorFieldFormatterCss object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, Token $token_service) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->tokenService = $token_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\FormatterPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'selector' => 'body',
      'property' => 'background-color',
      'important' => TRUE,
      'opacity' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['selector'] = [
      '#title' => $this->t('Selector'),
      '#description' => $this->t('A valid CSS selector such as <code>.links > li > a, #logo</code>.'),
      '#type' => 'textarea',
      '#rows' => '1',
      '#default_value' => $this->getSetting('selector'),
      '#required' => TRUE,
      '#placeholder' => 'body > div > a',
    ];
    $elements['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
    ];
    $elements['property'] = [
      '#title' => $this->t('Property'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('property'),
      '#required' => TRUE,
      '#options' => [
        'background-color' => $this->t('Background color'),
        'color' => $this->t('Text color'),
      ],
    ];
    $elements['important'] = [
      '#title' => $this->t('Important'),
      '#description' => $this->t('Whenever this declaration is more important than others.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('important'),
    ];

    if ($this->getFieldSetting('opacity')) {
      $elements['opacity'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display opacity'),
        '#default_value' => $this->getSetting('opacity'),
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $opacity = $this->getFieldSetting('opacity');
    $settings = $this->getSettings();

    $summary = [];

    $summary[] = $this->t('CSS selector : @css_selector', [
      '@css_selector' => $settings['selector'],
    ]);
    $summary[] = $this->t('CSS property : @css_property', [
      '@css_property' => $settings['property'],
    ]);
    $summary[] = $this->t('!important declaration : @important_declaration', [
      '@important_declaration' => (($settings['important']) ? $this->t('Yes') : $this->t('No')),
    ]);

    if ($opacity && $settings['opacity']) {
      $summary[] = $this->t('Display with opacity.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();

    $elements = [];

    $entity = $items->getEntity();
    $tokens = [
      $entity->getEntityType()->id() => $entity,
    ];
    foreach ($items as $item) {
      $value = $this->viewValue($item);
      $selector = $this->tokenService->replace(
        $settings['selector'],
        $tokens
      );
      $important = ($settings['important']) ? ' !important' : '';
      $property = $settings['property'];

      $inline_css = $selector . ' { ' . $property . ': ' . $value . $important . '; }';

      // @todo: Not sure this is the best way.
      // https://www.drupal.org/node/2391025
      // https://www.drupal.org/node/2274843
      $elements['#attached']['html_head'][] = [[
        '#tag' => 'style',
        '#value' => $inline_css,
      ], sha1($inline_css),
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(ColorFieldType $item) {
    $opacity = $this->getFieldSetting('opacity');
    $settings = $this->getSettings();

    $color_hex = new ColorHex($item->color, $item->opacity);

    if ($opacity && $settings['opacity']) {
      $rgbtext = $color_hex->toRgb()->toString(TRUE);
    }
    else {
      $rgbtext = $color_hex->toRgb()->toString(FALSE);
    }

    return $rgbtext;
  }

}
