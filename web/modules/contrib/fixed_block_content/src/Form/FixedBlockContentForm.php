<?php

namespace Drupal\fixed_block_content\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Fixed block content form.
 */
class FixedBlockcontentForm extends EntityForm implements ContainerInjectionInterface {

  use ConfigFormBaseTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fixed_block_content.fixed_block_content.' . $this->entity->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // We need at least one custom block type.
    $types = $this->entityTypeManager->getStorage('block_content_type')->loadMultiple();
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any block types yet. Go to the <a href=":url">block type creation page</a> to add a new block type.', [
          ':url' => Url::fromRoute('block_content.type_add')->toString(),
        ]),
      ];
    }

    $form = parent::form($form, $form_state);

    /** @var \Drupal\fixed_block_content\FixedBlockContentInterface $block */
    $block = $this->entity;

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $block->label(),
      '#description' => $this->t("The block title."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $block->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'source' => ['title'],
        'exists' => ['Drupal\fixed_block_content\Entity\FixedBlockContent', 'load'],
      ],
      '#disabled' => !$block->isNew(),
    ];

    // Block content type (bundle).
    $form['block_content_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Block content'),
      '#description' => $this->t('The block content type.'),
      '#options' => [],
      '#required' => TRUE,
      '#default_value' => $block->getBlockContentBundle(),
    ];

    /** @var \Drupal\block_content\Entity\BlockContentType $block_content_type */
    foreach ($types as $key => $block_content_type) {
      $form['block_content_bundle']['#options'][$key] = $block_content_type->label();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    // No actions if there are no form.
    return isset($form['title']) ? parent::actions($form, $form_state) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.fixed_block_content.collection');
    $this->entity->save();
  }

}
