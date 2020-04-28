<?php

namespace Drupal\telephone_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\telephone_formatter\FormatterInterface;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'telephone_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "telephone_formatter",
 *   label = @Translation("Formatted telephone"),
 *   field_types = {
 *     "telephone"
 *   }
 * )
 */
class TelephoneFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Formatter service.
   *
   * @var \Drupal\telephone_formatter\FormatterInterface
   */
  protected $formatter;

  /**
   * CountryManager service.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * Constructs a FormatterBase object.
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
   * @param \Drupal\telephone_formatter\FormatterInterface $formatter
   *   Formatter service.
   * @param \Drupal\Core\Locale\CountryManagerInterface $countryManager
   *   CountryManager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FormatterInterface $formatter, CountryManagerInterface $countryManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->formatter = $formatter;
    $this->countryManager = $countryManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('telephone_formatter.formatter'),
      $container->get('country_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format' => PhoneNumberFormat::INTERNATIONAL,
      'link' => TRUE,
      'default_country' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#description' => $this->t('List of available formats'),
      '#default_value' => $this->getSetting('format'),
      '#options' => self::availableFormats(),
    ];
    $elements['link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link'),
      '#description' => $this->t('Format as link'),
      '#default_value' => $this->getSetting('link'),
    ];

    $elements['default_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Default country'),
      '#description' => $this->t('If field allows internal telephone numbers you can choose which country this number belongs to by default. It is highly advised to enable telephone validation for this field to ensure that telephone number is valid and can be parsed and reformatted.'),
      '#default_value' => $this->getSetting('default_country'),
      '#options' => [NULL => $this->t('- Do not use default country -')] + $this->countryManager->getList(),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $formats = self::availableFormats();
    $summary[] = $this->t('Format: @format', ['@format' => $formats[$this->getSetting('format')]]);
    $summary[] = $this->t('Link: @link', ['@link' => $this->getSetting('link') ? $this->t('Yes') : $this->t('No')]);
    if ($default_country = $this->getSetting('default_country')) {
      $summary[] = $this->t('Default country: @default_country',
        ['@default_country' => $default_country]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      try {
        if ($this->getSetting('link')) {
          $element[$delta] = $this->viewLinkValue($item);
        }
        else {
          $element[$delta] = $this->viewFormattedValue($item);
        }
      }
      catch (\Exception $e) {
        $element[$delta] = $this->viewPlainValue($item);
      }
    }

    return $element;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field value.
   *
   * @return array
   *   The textual output generated as a render array.
   */
  protected function viewPlainValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output.
    return [
      '#type' => 'inline_template',
      '#template' => '{{ value }}',
      '#context' => ['value' => $item->value],
    ];
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field value.
   *
   * @return array
   *   The textual output generated as a render array.
   */
  protected function viewFormattedValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output.
    return [
      '#type' => 'inline_template',
      '#template' => '{{ value }}',
      '#context' => ['value' => $this->getFormattedValue($item)],
    ];
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field value.
   *
   * @return array
   *   The textual output generated as a render array.
   */
  protected function viewLinkValue(FieldItemInterface $item) {
    // Render each element as link.
    $element = [
      '#type' => 'link',
      '#title' => $this->getFormattedValue($item),
        // Url prepended with 'tel:' schema.
      '#url' => Url::fromUri($this->formatter->format($item->value, PhoneNumberFormat::RFC3966, $this->getSetting('default_country'))),
      '#options' => ['external' => TRUE],
    ];

    if (!empty($item->_attributes)) {
      $element['#options'] += ['attributes' => []];
      $element['#options']['attributes'] += $item->_attributes;
      // Unset field item attributes since they have been included in the
      // formatter output and should not be rendered in the field template.
      unset($item->_attributes);
    }
    return $element;
  }

  /**
   * Generate formatted output for one field item.
   *
   * Helper function which helps you get field value formatted based on field
   * formatter settings.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   Field item.
   *
   * @return string
   *   Returns preformatted telephone number.
   */
  protected function getFormattedValue(FieldItemInterface $item) {
    return $this->formatter->format(
      $item->value,
      $this->getSetting('format'),
      $this->getSetting('default_country')
    );
  }

  /**
   * List of available formats.
   */
  public static function availableFormats() {
    return [
      PhoneNumberFormat::INTERNATIONAL => t('International'),
      PhoneNumberFormat::E164 => t('E164'),
      PhoneNumberFormat::NATIONAL => t('National'),
      PhoneNumberFormat::RFC3966 => t('RFC3966'),
    ];
  }

}
