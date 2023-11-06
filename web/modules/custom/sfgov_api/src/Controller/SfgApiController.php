<?php

namespace Drupal\sfgov_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\sfgov_api\SfgApiPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for sfgov_api routes.
 */
class SfgApiController extends ControllerBase {

  /**
   * The sfgov_api utilities.
   *
   * @var Drupal\sfgov_api\SfgApiPluginManager
   */
  protected $sfgApiPluginManager;

  /**
   * Constructs a new SfgApiController.
   */
  public function __construct(SfgApiPluginManager $sfgApiPluginManager) {
    $this->sfgApiPluginManager = $sfgApiPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.sfgov_api'),
    );
  }

  /**
   * Builds the response.
   */
  public function viewEntityData($langcode, string $entity_type, string $bundle, $entity_id = NULL) {
    $response = ['error' => 'No plugin found for bundle: ' . $bundle];

    if (!$bundle) {
      $response = ['error' => 'Please specify a bundle.'];
    }
    if (!$entity_type) {
      $response = ['error' => 'Please specify an entity type.'];
    }

    if ($plugin_label = $this->sfgApiPluginManager->validatePlugin($entity_type, $bundle)) {
      $response = $this->sfgApiPluginManager->fetchJsonData($plugin_label, $langcode, $entity_id);
    }

    return new JsonResponse($response);
  }

}
