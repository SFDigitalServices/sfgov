<?php

namespace Drupal\sfgov_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for sfgov_api routes.
 */
class SfgovApiController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function fetchEntityData(string $entity_type, string $bundle, $langcode = 'en', $entity_id = NULL) {
    if (!$bundle) {
      return new JsonResponse(['error' => 'Please specify a bundle.']);
    }
    if (!$entity_type) {
      return new JsonResponse(['error' => 'Please specify an entity type.']);
    }

    $sfgov_api_plugin_manager = \Drupal::service('plugin.manager.sfgov_api');
    $available_plugins = $sfgov_api_plugin_manager->getDefinitions();
    $plugin_label = "{$entity_type}_{$bundle}";
    if (in_array($plugin_label, array_keys($available_plugins))) {
      $plugin = $sfgov_api_plugin_manager->createInstance($plugin_label, [
        'langcode' => $langcode,
        'entity_id' => $entity_id,
      ]);
      return $plugin->sendJsonResponse();
    }
    else {
      return new JsonResponse(['error' => 'No plugin found for bundle: ' . $bundle]);
    }
  }
}
