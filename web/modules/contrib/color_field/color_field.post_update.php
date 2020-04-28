<?php

/**
 * @file
 * Contains post update functionality for the color field module.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Update spectrum widget configuration to allow multiple palettes.
 */
function color_field_post_update_spectrum_palette() {
  /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $entity_form_display */
  foreach (EntityFormDisplay::loadMultiple() as $entity_form_display) {
    $changed = FALSE;
    foreach ($entity_form_display->getComponents() as $name => $options) {
      if (isset($options['type']) && $options['type'] === 'color_field_widget_spectrum') {
        if ($options['settings']['palette']) {
          $palette = explode(',', $options['settings']['palette']);
          foreach ($palette as &$color) {
            $color = '"' . trim($color) . '"';
          }
          $options['settings']['palette'] = '[' . implode(',', $palette) . ']';
          $entity_form_display->setComponent($name, $options);
          $changed = TRUE;
        }
      }
    }

    if ($changed) {
      $entity_form_display->save();
    }
  }

  return t('The new palette format for spectrum color field widgets has been applied.');
}
