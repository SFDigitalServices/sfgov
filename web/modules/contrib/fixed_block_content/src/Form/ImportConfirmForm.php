<?php

namespace Drupal\fixed_block_content\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Import default content confirm form class.
 */
class ImportConfirmForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The current default content will be be lost.') . ' ' . parent::getDescription();
  }

  /**
   * Gathers a confirmation question.
   *
   * @return string
   *   Translated string.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to set the %block current content as the default?', [
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
    // Import the current content block contents and store it into config.
    $this->entity->importDefaultContent();

    // Set a message that the entity was deleted.
    drupal_set_message($this->t('Current contents of block %label saved as the default content.', [
      '%label' => $this->entity->label(),
    ]));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
