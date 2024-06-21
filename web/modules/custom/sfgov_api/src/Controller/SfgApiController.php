<?php

namespace Drupal\sfgov_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\sfgov_api\SfgApiPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SfgApiController.
   */
  public function __construct(SfgApiPluginManager $sfgApiPluginManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->sfgApiPluginManager = $sfgApiPluginManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.sfgov_api'),
      $container->get('entity_type.manager')
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
      $display[]['error'] = 'Please specify a shape (wag, stub, or raw).';
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
   * View the entity info for the associated arguments.
   */
  public function viewEntityInfo($entity_type, $bundle, $filter) {
    $display = [];
    if (empty($entity_type)) {
      $display[]['error'] = 'Please specify an entity type.';
    }
    if (empty($bundle)) {
      $display[]['error'] = 'Please specify a bundle.';
    }
    if (empty($filter)) {
      $display[]['error'] = 'Please specify a filter.';
    }

    if (empty($display)) {
      if ($filter === 'id') {
        $bundle_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('bundle');
        $query = $this->entityTypeManager->getStorage($entity_type)->getQuery()
          ->accessCheck(FALSE)
          ->condition($bundle_key, $bundle);
        $entity_ids = $query->execute();
        $display = array_values($entity_ids);
      }
    }

    return new JsonResponse($display);
  }

}
