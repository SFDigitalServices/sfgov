<?php

namespace Drupal\entity_reference_unpublished\Plugin\EntityReferenceSelection;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Unpublished content plugin of the Entity Reference Selection plugin.
 *
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
 * @see \Drupal\Core\Entity\Annotation\EntityReferenceSelection
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
 * @see \Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver
 * @see plugin_api
 *
 * @EntityReferenceSelection(
 *   id = "unpublished",
 *   label = @Translation("Unpublished Default"),
 *   entity_types = {"node"},
 *   group = "unpublished",
 *   weight = 0,
 * )
 */
class UnpublishedNodeSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['target_bundles']['#title'] = $this->t('Content types');

    return $form;
  }

}
