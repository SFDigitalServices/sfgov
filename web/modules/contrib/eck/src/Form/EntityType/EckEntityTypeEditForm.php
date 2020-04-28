<?php

namespace Drupal\eck\Form\EntityType;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the edit form for ECK Entity Type.
 *
 * @ingroup eck
 */
class EckEntityTypeEditForm extends EckEntityTypeFormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    // Change the submit button value.
    $actions['submit']['#value'] = $this->t('Update @type', ['@type' => $this->entity->label()]);

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $manager = \Drupal::entityTypeManager();
    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $fieldStorage */
    $fieldStorage = $manager->getStorage($this->entity->id());
    $definitions = $fieldStorage->getFieldStorageDefinitions();
    foreach (['title', 'created', 'changed', 'uid'] as $field) {
      if (isset($definitions[$field]) && $fieldStorage->countFieldData($definitions[$field], TRUE)) {
        $form['base_fields'][$field]['#disabled'] = TRUE;
      }
    }

    return $form;
  }

}
