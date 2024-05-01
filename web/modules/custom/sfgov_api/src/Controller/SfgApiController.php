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
   * View the data that the API will send with the associated arguments.
   */
  public function viewEntityData(string $shape, $langcode, string $entity_type, string $bundle, $entity_id) {
    $plugin_label = $this->sfgApiPluginManager->validatePlugin($entity_type, $bundle);

    $display = [];
    if (empty($plugin_label)) {
      $display[]['error'] = 'No plugin found for bundle: ' . $bundle;
    }

    if (empty($shape)) {
      $display[]['error'] = 'Please specify a shape (wag or raw).';
    }

    if (empty($bundle)) {
      $display[]['error'] = 'Please specify a bundle.';
    }
    if (empty($entity_type)) {
      $display[]['error'] = 'Please specify an entity type.';
    }
    if (empty($langcode)) {
      $display[]['error'] = 'Please specify a language code.';
    }
    if (empty($entity_id)) {
      $display[]['error'] = 'Please specify an entity id.';
    }

    if (empty($display)) {
      $payload = $this->sfgApiPluginManager->fetchPayload($shape, $plugin_label, $langcode, $entity_id);
      if ($errors = $payload->getErrors()) {
        $display = $errors;
      }
      else {
        $display = $payload->getPayloadData();
      }
    }

    return new JsonResponse($display);
  }

  /**
   * View the reference chain for the given plugin and direction.
   */
  public function viewReferenceChain($direction, $plugin_label = '') {
    $display = [];
    if ($direction !== 'complete' && empty($plugin_label)) {
      $display[]['error'] = 'Please specify a plugin label.';
    }

    if (empty($display)) {
      if ($direction == 'complete') {
        $display = $this->sfgApiPluginManager->referenceChainComplete();
      }
      if ($direction == 'up') {
        $display = $this->sfgApiPluginManager->referenceChainUp($plugin_label);
      }
      if ($direction == 'down') {
        $display = $this->sfgApiPluginManager->referenceChainDown($plugin_label);
      }
    }

    return new JsonResponse($display);
  }

}
