<?php

namespace Drupal\tmgmt\Entity\ListBuilder;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\TranslatorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of translators.
 */
class TranslatorListBuilder extends DraggableListBuilder implements EntityListBuilderInterface {
  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\tmgmt\TranslatorManager $translatorManager
   */
  protected $translatorManager;

  /**
   * Constructs a TranslatorListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The config storage definition.
   * @param \Drupal\tmgmt\TranslatorManager $translator_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, TranslatorManager $translator_manager) {
    parent::__construct($entity_type, $storage);
    $this->storage = $storage;
    $this->translatorManager = $translator_manager;
  }

  /**
   * Creates the instance of the list builder.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container entity.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type which should be created.
   *
   * @return TranslatorListBuilder
   *   The created instance of out list builder.
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.tmgmt.translator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_translator_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['logo'] = t('Provider');
    $installed_translators = $this->translatorManager->getLabels();
    if (empty($installed_translators)) {
      $this->messenger()->addError(t("There are no provider plugins available. Please install a provider plugin."));
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['clone'] = array(
      'url' => $entity->toUrl('clone-form'),
      'title' => t('Clone'),
      'weight' => 50,
    );
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();

    // Add provider logo.
    /** @var \Drupal\tmgmt\Entity\Translator $entity */
    $definition = \Drupal::service('plugin.manager.tmgmt.translator')->getDefinition($entity->getPluginId());
    if (isset($definition['logo'])) {
      $logo_render_array = [
        '#theme' => 'image',
        '#uri' => file_create_url(drupal_get_path('module', $definition['provider']) . '/' . $definition['logo']),
        '#alt' => $definition['label'],
        '#title' => $definition['label'],
        '#attributes' => [
          'class' => 'tmgmt-logo-overview',
        ],
      ];
    }
    $row['logo'] = isset($logo_render_array) ? $logo_render_array : ['#markup' => $definition['label']];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'tmgmt/admin';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger()->addStatus(t('The order of the translators has been saved.'));
  }

}
