<?php

namespace Drupal\fixed_block_content\Form;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityDeleteFormTrait;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fixed block content entity deletion form.
 */
class FixedBlockContentDeleteForm extends EntityConfirmFormBase {

  use EntityDeleteFormTrait {
    submitForm as traitSubmitForm;
  }

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * FixedBlockContentDeleteForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The config manager.
   */
  public function __construct(ConfigManagerInterface $config_manager) {
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    /** @var \Drupal\fixed_block_content\FixedBlockContentInterface $entity */
    $entity = $this->getEntity();
    $this->addDependencyListsToForm($form, $entity->getConfigDependencyKey(), [$entity->getConfigDependencyName()], $this->configManager, $this->entityManager);

    // Option to delete the linked block.
    if ($block_content = $entity->getBlockContent(FALSE)) {
      $form['delete_linked_block'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Delete the linked custom block as well'),
        '#description' => $this->t('Check to delete the custom block %title (@id).', ['%title' => $block_content->label(), '@id' => $block_content->id()]),
        '#default_value' => FALSE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->traitSubmitForm($form, $form_state);

    // Delete the linked block content as requested.
    if ($form_state->getValue('delete_linked_block')
      && ($block_content = $this->getEntity()->getBlockContent(FALSE))) {
      $block_content->delete();
    }
  }

}
