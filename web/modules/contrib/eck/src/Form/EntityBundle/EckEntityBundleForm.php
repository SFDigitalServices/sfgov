<?php

namespace Drupal\eck\Form\EntityBundle;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for ECK entity bundle forms.
 *
 * @ingroup eck
 */
class EckEntityBundleForm extends EntityForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity_type_id = $this->entity->getEntityType()->getBundleOf();
    $type = $this->entity;
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->create([
      'type' => $this->operation == 'add' ? $type->uuid() : $type->id(),
    ]
    );
    $type_label = $entity->getEntityType()->getLabel();

    $form['name'] = [
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->name,
      '#description' => t(
        'The human-readable name of this entity bundle. This text will be displayed as part of the list on the <em>Add @type content</em> page. This name must be unique.',
        ['@type' => $type_label]),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['type'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $type->isLocked(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['name'],
      ],
      '#description' => t(
        'A unique machine-readable name for this entity type bundle. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the Add %type content page, in which underscores will be converted into hyphens.',
        [
          '%type' => $type_label,
        ]
      ),
    ];

    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->description,
      '#description' => t(
        'Describe this entity type bundle. The text will be displayed on the <em>Add @type content</em> page.',
        ['@type' => $type_label]
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save bundle');
    $actions['delete']['#value'] = t('Delete bundle');

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName(
        'type',
        $this->t(
          "Invalid machine-readable name. Enter a name other than %invalid.",
          ['%invalid' => $id]
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $type = $this->entity;
    $type->type = trim($type->id());
    $type->name = trim($type->name);

    $status = $type->save();

    $t_args = ['%name' => $type->label()];

    if ($status == SAVED_UPDATED) {
      \Drupal::messenger()->addMessage($this->t('The entity bundle %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      \Drupal::messenger()->addMessage($this->t('The entity bundle %name has been added.', $t_args));
      $context = array_merge(
        $t_args,
        [
          'link' => Link::fromTextAndUrl(t('View'), new Url('eck.entity.' . $type->getEntityType()
            ->getBundleOf() . '_type.list'))->toString(),
        ]
      );
      $this->logger($this->entity->getEntityTypeId())
        ->notice('Added entity bundle %name.', $context);
    }

    $form_state->setRedirect(
      'eck.entity.' . $type->getEntityType()->getBundleOf() . '_type.list'
    );
  }

  /**
   * Checks for an existing ECK bundle.
   *
   * @param string $type
   *   The bundle type.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this bundle already exists in the entity type, FALSE otherwise.
   */
  public function exists($type, array $element, FormStateInterface $form_state) {
    $bundleStorage = \Drupal::entityTypeManager()->getStorage($this->entity->getEckEntityTypeMachineName() . '_type');
    return (bool) $bundleStorage->load($type);
  }

}
