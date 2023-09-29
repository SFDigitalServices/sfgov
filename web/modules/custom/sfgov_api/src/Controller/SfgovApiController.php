<?php

namespace Drupal\sfgov_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\sfgov_api\SfgovApiPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for sfgov_api routes.
 */
class SfgovApiController extends ControllerBase {

  /**
   * The sfgov_api plugin manager.
   *
   * @var \Drupal\sfgov_api\SfgovApiPluginManager
   */
  protected $sfgovApiPluginManager;

  /**
   * Constructs a new SfgovApiController.
   *
   * @param \Drupal\sfgov_api\SfgovApiPluginManager $sfgovApiPluginManager
   *   The sfgov_api plugin manager.
   */
  public function __construct(SfgovApiPluginManager $sfgovApiPluginManager) {
    $this->sfgovApiPluginManager = $sfgovApiPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.sfgov_api')
    );
  }

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

    $available_plugins = $this->sfgovApiPluginManager->getDefinitions();
    $plugin_label = "{$entity_type}_{$bundle}";
    if (in_array($plugin_label, array_keys($available_plugins))) {
      $plugin = $this->sfgovApiPluginManager->createInstance($plugin_label, [
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
