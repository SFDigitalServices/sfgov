<?php

namespace Drupal\sfgov_locations\Plugin\Field\FieldType;

use Drupal\address\Plugin\Field\FieldType\AddressItem as AddressItemBase;
use Drupal\sfgov_locations\AddressField;
use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use Drupal\sfgov_locations\FieldOverrides;
use Drupal\address\AddressInterface;
use Drupal\sfgov_locations\LabelHelper;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'address' field type.
 */
class AddressItem extends AddressItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns'][AddressField::ADDRESSEE] = [
      'type' => 'varchar',
      'length' => 255,
    ];
    $schema['columns'][AddressField::LOCATION_NAME] = [
      'type' => 'varchar',
      'length' => 255,
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['addressee'] = DataDefinition::create('string')
      ->setLabel(t('Addressee.'));
    $properties['location_name'] = DataDefinition::create('string')
      ->setLabel(t('Location name.'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return self::defaultCountrySettings() + [
      'langcode_override' => '',
      'field_overrides' => [],
      // Replaced by field_overrides.
      'fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $languages = \Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    $language_options = [];
    foreach ($languages as $langcode => $language) {
      // Only list real languages (English, French, but not "Not specified").
      if (!$language->isLocked()) {
        $language_options[$langcode] = $language->getName();
      }
    }

    $element = $this->countrySettingsForm($form, $form_state);
    $element['langcode_override'] = [
      '#type' => 'select',
      '#title' => $this->t('Language override'),
      '#description' => $this->t('Ensures entered addresses are always formatted in the same language.'),
      '#options' => $language_options,
      '#default_value' => $this->getSetting('langcode_override'),
      '#empty_option' => $this->t('- No override -'),
      '#access' => \Drupal::languageManager()->isMultilingual(),
    ];

    $element['field_overrides_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Field overrides'),
      '#description' => $this->t('Use field overrides to override the country-specific address format, forcing specific properties to always be hidden, optional, or required.'),
    ];
    $element['field_overrides'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Property'),
        $this->t('Override'),
      ],
      '#element_validate' => [[get_class($this), 'fieldOverridesValidate']],
    ];
    $field_overrides = $this->getFieldOverrides();
    foreach (LabelHelper::getGenericFieldLabels() as $field_name => $label) {
      $override = isset($field_overrides[$field_name]) ? $field_overrides[$field_name] : '';

      $element['field_overrides'][$field_name] = [
        'field_label' => [
          '#type' => 'markup',
          '#markup' => $label,
        ],
        'override' => [
          '#type' => 'select',
          '#options' => [
            FieldOverride::HIDDEN => $this->t('Hidden'),
            FieldOverride::OPTIONAL => $this->t('Optional'),
            FieldOverride::REQUIRED => $this->t('Required'),
          ],
          '#default_value' => $override,
          '#empty_option' => $this->t('- No override -'),
        ],
      ];
    }

    return $element;
  }

  /**
   * Gets the field overrides for the current field.
   *
   * @return array
   *   FieldOverride constants keyed by AddressField constants.
   */
  public function getFieldOverrides() {
    $field_overrides = [];
    if ($fields = $this->getSetting('fields')) {
      $unused_fields = array_diff(AddressField::getAll(), $fields);
      foreach ($unused_fields as $field) {
        $field_overrides[$field] = FieldOverride::HIDDEN;
      }
    }
    elseif ($overrides = $this->getSetting('field_overrides')) {
      foreach ($overrides as $field => $data) {
        $field_overrides[$field] = $data['override'];
      }
    }

    return $field_overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    $constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
    $field_overrides = new FieldOverrides($this->getFieldOverrides());
    $constraints[] = $constraint_manager->create('ComplexData', [
      'country_code' => [
        'Country' => [
          'availableCountries' => $this->getAvailableCountries(),
        ],
      ],
    ]);
    $constraints[] = $constraint_manager->create('AddressFormat', ['fieldOverrides' => $field_overrides]);

    return $constraints;
  }

  /**
   * Get addressee.
   */
  public function getAddressee() {
    return $this->addressee;
  }

  /**
   * Get location name.
   */
  public function getLocation_name() {
    return $this->location_name;
  }

}
