<?php

namespace Drupal\sfgov_departments\Plugin\GroupContentEnabler;

use Drupal\media\Entity\MediaType;
use Drupal\Component\Plugin\Derivative\DeriverBase;

class GroupMediaDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (MediaType::loadMultiple() as $name => $type) {
      $label = $type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group media (@type)', ['@type' => $label]),
        'description' => t('Adds %type media to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
