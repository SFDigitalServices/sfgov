<?php

namespace Drupal\entity_reference_unpublished\Plugin\EntityReferenceSelection;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Unpublished media plugin of the Entity Reference Selection plugin.
 *
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
 * @see \Drupal\Core\Entity\Annotation\EntityReferenceSelection
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
 * @see \Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver
 * @see plugin_api
 *
 * @EntityReferenceSelection(
 *   id = "unpublished_media",
 *   label = @Translation("Unpublished Media"),
 *   entity_types = {"media"},
 *   group = "unpublished_media",
 *   weight = 0,
 * )
 */
class UnpublishedMediaSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['target_bundles']['#title'] = $this->t('Media');

    return $form;
  }

}
