<?php

namespace Drupal\sfgov_admin\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\field\FieldConfigInterface;
use Drupal\paragraphs\Plugin\Field\FieldWidget\InlineParagraphsWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field widget with options to customize the referenced paragraph types form display.
 *
 * @FieldWidget(
 *   id = "sfgov_customizable_paragraphs",
 *   label = @Translation("Customizable Paragraphs (SF.gov)"),
 *   description = @Translation("A widget with options to customize the referenced paragraph types form display."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class SfgovCustomizableParagraphsWidget extends InlineParagraphsWidget {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * SfgovCustomizableParagraphsSectionWidget constructor.
   *
   * @param $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
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
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'paragraph_settings' => []
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('paragraph');
    $paragraph_settings = $this->getSetting('paragraph_settings');

    $element['paragraph_settings'] = [
      '#title' => $this->t('Paragraph settings'),
      '#type' => 'details',
      '#open' => TRUE,
      '#description' => $this->t('Customize the form display for the referenced paragraph types.'),
    ];

    $handler_settings = $this->fieldDefinition->getSetting('handler_settings');
    foreach ($handler_settings['target_bundles'] as $bundle) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions('paragraph', $bundle);

      // Filter out base fields definitions.
      $custom_field_definitions = array_filter($field_definitions, function ($field_definition) {
        return $field_definition instanceof FieldConfigInterface;
      });

      if (!count($custom_field_definitions)) {
        continue;
      }

      $element['paragraph_settings'][$bundle] = [
        '#title' => $bundle_info[$bundle]['label'],
        '#type' => 'fieldset',
      ];

      $element['paragraph_settings'][$bundle]['field_settings'] = [
        '#type' => 'container',
      ];

      foreach ($custom_field_definitions as $field_name => $field_definition) {
        $field_settings = $paragraph_settings[$bundle]['field_settings'][$field_name];
        $form_key = $field_name;
        $element['paragraph_settings'][$bundle]['field_settings'][$form_key] = [
          '#title' => $field_definition->getLabel(),
          '#type' => 'details',
        ];

        $element['paragraph_settings'][$bundle]['field_settings'][$form_key]['label'] = [
          '#title' => $this->t('Label'),
          '#description' => $this->t('Provide a custom label for the field. Leave blank to use default.'),
          '#type' => 'textfield',
          '#default_value' => $field_settings['label'],
        ];

        if ($field_definition->getType() == 'entity_reference_revisions' && $field_definition->getSetting('target_type') == 'paragraph') {
          $field_handler_settings = $field_definition->getSetting('handler_settings');
          $target_bundle_options = array_reduce($field_handler_settings['target_bundles'], function ($return, $bundle) use ($bundle_info) {
            $return[$bundle] = $bundle_info[$bundle]['label'];
            return $return;
          }, []);

          $element['paragraph_settings'][$bundle]['field_settings'][$form_key]['target_bundles'] = [
            '#title' => $this->entityTypeManager->getDefinition('paragraph')->getBundleLabel(),
            '#type' => 'checkboxes',
            '#options' => $target_bundle_options,
            '#default_value' => $field_settings['target_bundles'],
            '#multiple' => TRUE,
          ];
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Bail out if this is not a paragraph subform.
    if (!isset($element['#paragraph_type'])) {
      return $element;
    }

    $bundle = $element['#paragraph_type'];
    $paragraph_settings = $this->getSetting('paragraph_settings');

    foreach ($paragraph_settings[$bundle]['field_settings'] as $field_name => $field_setting) {
      if (!isset($element['subform'][$field_name])) {
        continue;
      }

      if (trim($field_setting['label']) !== "") {
        $element['subform'][$field_name]['widget']['#title'] = $field_setting['label'];

        if (!$element['subform'][$field_name]['widget']['#cardinality_multiple']) {
          foreach (Element::children($element['subform'][$field_name]['widget']) as $child) {
            $element['subform'][$field_name]['widget'][$child]['value']['#title'] = $field_setting['label'];
          }
        }
      }

      if (isset($field_setting['target_bundles']) && isset($element['subform'][$field_name]['widget']['add_more'])) {
        foreach ($field_setting['target_bundles'] as $bundle => $enabled) {
          if ((bool) $enabled) {
            continue;
          }

          $button_key = 'add_more_button_' . $bundle;
          $element['subform'][$field_name]['widget']['add_more'][$button_key]['#access'] = FALSE;
          if (isset($element['subform'][$field_name]['widget']['add_more']['operations'])) {
            unset($element['subform'][$field_name]['widget']['add_more']['operations']['#links'][$button_key]);
          }
        }

        if (isset($element['subform'][$field_name]['widget']['add_more']['add_more_select'])) {
          $options = $element['subform'][$field_name]['widget']['add_more']['add_more_select']['#options'];
          $element['subform'][$field_name]['widget']['add_more']['add_more_select']['#options'] = array_intersect_key($options, array_filter($field_setting['target_bundles']));
        }
      }
    }

    return $element;
  }

}
