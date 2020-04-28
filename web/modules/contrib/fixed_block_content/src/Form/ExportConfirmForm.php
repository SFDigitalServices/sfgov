<?php

namespace Drupal\fixed_block_content\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Export default content confirm form class.
 */
class ExportConfirmForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The current content of the block will be lost.') . ' ' . parent::getDescription();
  }

  /**
   * Gathers a confirmation question.
   *
   * @return string
   *   Translated string.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to restore the %block to its default content?', [
      '%block' => $this->entity->label(),
    ]);
  }

  /**
   * Implements getCancelUrl().
   *
   * @return \Drupal\Core\Url
   *   Cancel URL.
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.fixed_block_content.collection');
  }

  /**
   * The submit handler for the confirm form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Export the content stored in config to the block content.
    $this->entity->exportDefaultContent();

    // Set a message that the entity was deleted.
    drupal_set_message($this->t('Block %label restored to default content.', [
      '%label' => $this->entity->label(),
    ]));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
