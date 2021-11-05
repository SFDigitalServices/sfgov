<?php

namespace Drupal\bulk_update_fields\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;




/**
 * Class BulkUpdateExcludeForm.
 */
class BulkUpdateExcludeForm extends ConfigFormBase {


  private $entityFieldManager;

  /**
   * EntityTypeBundleInfo.
   *
   * @var Drupal\Core\Entity\EntityTypeBundleInfo
   */
  private $entityTypeBundleInfo;

  /**
   * EntityTypeManager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructor.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entityTypeManager.
   * @param Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info
   *   The EntityTypeBundleInfo.
   * @param Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The EntityFieldManagerInterface.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfo $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bulk_update_fields.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_update_exclude_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bulk_update_fields.settings');

    $excluded_base_fields = [
      'nid',
      'uuid',
      'vid',
      'type',
      'revision_uid',
      'title',
      'menu_link',
      'status',
      'uid',
      'default_langcode',
      'revision_timestamp',
      'revision_log',
      'created',
      'changed',
      'pass',
      'name',
      'mail',
      'init',
    ];

    $bundles = $this->getFields('node');
    $uniques = [];
    foreach ($bundles as $k => $bundle) {
      foreach ($bundle as $field) {
        $uniques[] = $field->getName();
        if (!in_array($field->getName(), $excluded_base_fields) && !isset($options[$field->getName()])) {
          $options[$field->getName()]['field_name'] = $field->getLabel() . ' (' . $field->getName() . ')';
        }
      }
    }
    $header = [
      'field_name' => $this->t('Field Name'),
    ];
    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $config->get('exclude') ? $config->get('exclude') : '',
      '#empty' => $this->t('No fields found'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Get All fields.
   *
   * @param string $entity_type_id
   *   Entity type id.
   *
   * @return array
   *   Return all fields.
   */
  protected function getFields($entity_type_id) {
    // Get all bundles.
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    foreach ($bundles as $k => $bundle) {
      $fields[$k] = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $k);
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('bulk_update_fields.settings')
      ->set('exclude', $form_state->getValue('table'))
      ->save();
  }

}
