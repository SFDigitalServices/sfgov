<?php

namespace Drupal\sfgov_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManager;
use Drupal\sfgov_api\SfgApiPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for sfgov_api routes.
 */
class SfgApiController extends ControllerBase {

  /**
   * The sfgov_api plugin manager.
   *
   * @var \Drupal\sfgov_api\SfgApiPluginManager
   */
  protected $sfgApiPluginManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The pager manager.
   *
   * @var \Drupal\Core\Pager\PagerManager
   */
  protected $pagerManager;

  /**
   * Constructs a new SfgApiController.
   *
   * @param \Drupal\sfgov_api\SfgApiPluginManager $sfgApiPluginManager
   *   The sfgov_api plugin manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Pager\PagerManager $pager_manager
   *   The pager manager.
   */
  public function __construct(SfgApiPluginManager $sfgApiPluginManager, RequestStack $request_stack, PagerManager $pager_manager) {
    $this->sfgApiPluginManager = $sfgApiPluginManager;
    $this->requestStack = $request_stack;
    $this->pagerManager = $pager_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.sfgov_api'),
      $container->get('request_stack'),
      $container->get('pager.manager'),
    );
  }

  /**
   * Builds the response.
   */
  public function fetchEntityData($langcode, string $entity_type, string $bundle, $entity_id = NULL) {
    $response = ['error' => 'No plugin found for bundle: ' . $bundle];

    if (!$bundle) {
      $response = ['error' => 'Please specify a bundle.'];
    }
    if (!$entity_type) {
      $response = ['error' => 'Please specify an entity type.'];
    }

    $available_plugins = $this->sfgApiPluginManager->getDefinitions();
    $plugin_label = "{$entity_type}_{$bundle}";

    if (in_array($plugin_label, array_keys($available_plugins))) {
      $response = $this->fetchJsonData($plugin_label, $langcode, $entity_id);
    }

    return new JsonResponse($response);
  }

  /**
   * Fetch the data from the plugin.
   *
   * @param string $plugin_label
   *   The plugin label.
   * @param string $langcode
   *   The language code.
   * @param int $entity_id
   *   The entity id.
   */
  public function fetchJsonData($plugin_label, $langcode, $entity_id = NULL) {
    $plugin = $this->sfgApiPluginManager->createInstance($plugin_label, [
      'langcode' => $langcode,
      'entity_id' => $entity_id,
    ]);
    $entities = $plugin->getEntitiesList();
    $prepared_data = $plugin->renderEntities($entities);
    // $pager = $this->createPager($prepared_data);
    return $prepared_data;
  }

  /**
   * Add a pager to the data being rendered.
   *
   * @param array $data
   *   The data to be paged.
   *
   * @return array
   *   The paged data.
   */
  public function createPager($data) {
    return $data;
  }

}
