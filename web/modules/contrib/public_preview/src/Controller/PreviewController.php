<?php

namespace Drupal\public_preview\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\node\NodeInterface;
use Drupal\public_preview\Storage\PreviewStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function max;

/**
 * Class PreviewController.
 *
 * @package Drupal\public_preview\Controller
 */
class PreviewController extends ControllerBase {

  /**
   * Page cache kill switch service.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $cacheKillSwitch;

  /**
   * The preview storage.
   *
   * @var \Drupal\public_preview\Storage\PreviewStorageInterface
   */
  protected $previewStorage;

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('page_cache_kill_switch'),
      $container->get('public_preview.preview_storage')
    );
  }

  /**
   * PreviewController constructor.
   *
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $cacheKillSwitch
   *   Service for disabling page cache.
   * @param \Drupal\public_preview\Storage\PreviewStorageInterface $previewStorage
   *   The preview storage.
   */
  public function __construct(
    KillSwitch $cacheKillSwitch,
    PreviewStorageInterface $previewStorage
  ) {
    $this->cacheKillSwitch = $cacheKillSwitch;
    $this->previewStorage = $previewStorage;
  }

  /**
   * Render a node according to the hash.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $hash
   *   A hash that was generated by the module.
   *
   * @return array
   *   The render array of the node.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Exception
   */
  public function createPreview(NodeInterface $node, $hash) {
    $preparedNode = $this->prepareNodePreview($node, $hash);
    $langcode = $preparedNode->language()->getId();

    $viewBuilder = $this->entityTypeManager()->getViewBuilder($preparedNode->getEntityTypeId());
    $build = $viewBuilder->view($preparedNode, 'full', $langcode);
    $build['#cache']['max-age'] = 0;

    return $build;
  }

  /**
   * Generate page title.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param string $hash
   *   The hash.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Exception
   */
  public function getTitle(NodeInterface $node, $hash) {
    $preparedNode = $this->prepareNodePreview($node, $hash);
    $langcode = $preparedNode->language()->getId();

    return $this->t('Preview for the "@language" version of "@node_title"', [
      '@node_title' => $preparedNode->getTitle(),
      '@language' => $preparedNode->getTranslation($langcode)->language()->getName(),
    ]);
  }

  /**
   * Prepare the node for previewing.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The current node.
   * @param string $hash
   *   The preview hash.
   *
   * @return \Drupal\node\NodeInterface
   *   The latest revision of the translated node.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \InvalidArgumentException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Exception
   */
  protected function prepareNodePreview(NodeInterface $node, $hash) {
    // @todo: Add previewer service, and move this function there.
    $this->cacheKillSwitch->trigger();
    $langcode = $this->loadLangcode($hash);
    $latestRevision = $this->getLatestRevision($node);
    return $latestRevision->getTranslation($langcode);
  }

  /**
   * Return the latest revision of the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\NodeInterface|\Drupal\Core\Entity\EntityInterface
   *   The latest revision of the node.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getLatestRevision(NodeInterface $node) {
    // @todo: Add previewer service, and move this function there.
    /** @var \Drupal\node\NodeStorageInterface $nodeStorage */
    $nodeStorage = $this->entityTypeManager()->getStorage('node');
    $revIds = $nodeStorage->revisionIds($node);
    $latestRevId = max($revIds);

    return $nodeStorage->loadRevision($latestRevId);
  }

  /**
   * Helper function for loading the language code.
   *
   * @param string $hash
   *   The hash.
   *
   * @return string
   *   The langcode.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Exception
   */
  protected function loadLangcode($hash) {
    // @todo: Add previewer service, and move this function there.
    $preview = $this->previewStorage->load(['hash' => $hash]);

    if (FALSE === $preview) {
      throw new NotFoundHttpException();
    }

    return $preview->langcode;
  }

}
