<?php
/**
 * @file
 * Contains \Drupal\toc_api\Plugin\Block\TocBlockBase.
 */

namespace Drupal\toc_api\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a base TOC block which displays the current TOC module's TOC in a block.
 */
abstract class TocBlockBase extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $toc = $this->getCurrentToc();

    // Build the TOC.
    $options = $toc->getOptions();
    $build = [
      '#theme' => 'toc_' . $options['template'],
      '#toc' => $toc,
    ];

    // Set custom title.
    if ($title = $toc->getTitle()) {
      $build['#title'] = $title;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $this->getCurrentTocId();
    /** @var \Drupal\toc_api\TocManagerInterface $toc_manager */
    $toc_manager = \Drupal::service('toc_api.manager');

    // Get the new TOC instance and see if it is visible and should be
    // displayed in a block.
    $toc = $toc_manager->getToc($this->getCurrentTocId());

    if (!$toc || !$toc->isVisible() || !$toc->isBlock()) {
      return AccessResult::forbidden();
    }
    else {
      return AccessResult::allowed();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['route'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $node = $this->getCurrentNode();
    return ($node) ? ['node:' . $node->id()] : [];
  }

  /**
   * Get the current requests TOC object instance ID.
   *
   * Most TOC modules should use just the the modules name space which
   * can all be used as this block's plugin ID.
   *
   * @return string
   *   The current TOC block's plugin ID.
   */
  protected function getCurrentTocId() {
    return $this->pluginId;
  }

  /**
   * Get the current request TOC object instance.
   *
   * @return \Drupal\toc_api\TocInterface
   *   A TOC object.
   */
  protected function getCurrentToc() {
    /** @var \Drupal\toc_api\TocManagerInterface $toc_manager */
    $toc_manager = \Drupal::service('toc_api.manager');

    // Get the new TOC instance using the module name.
    return $toc_manager->getToc($this->getCurrentTocId());
  }

  /**
   * Load the node associated with the current request.
   *
   * @return \Drupal\node\NodeInterface|null
   *   A node entity, or NULL if no node is not found.
   */
  protected function getCurrentNode() {
    switch (\Drupal::routeMatch()->getRouteName()) {
      // Look at the request's node revision.
      case 'node.revision_show':
        return node_revision_load(\Drupal::routeMatch()->getParameter('node_revision'));

      // Look at the request's node preview.
      case 'entity.node.preview':
        return \Drupal::routeMatch()->getParameter('node_preview');

      // Look at the request's node.
      case 'entity.node.canonical':
        return \Drupal::routeMatch()->getParameter('node');
    }

    return NULL;
  }

}
