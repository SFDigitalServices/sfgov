<?php
/**
 * @file
 * Contains \Drupal\toc_filter\Plugin\Block\TocFilterBlock.
 */

namespace Drupal\toc_filter\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\toc_api\Plugin\Block\TocBlockBase;

/**
 * Provides a 'TOC filter' block.
 *
 * @Block(
 *   id = "toc_filter",
 *   admin_label = @Translation("Table of contents"),
 *   category = @Translation("TOC filter")
 * )
 */
class TocFilterBlock extends TocBlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $node = $this->getCurrentNode();

    // If current page is not a node or does not contain a [toc] token return
    // forbidden access result.
    if (!$node || !$node->hasField('body') || stripos($node->body->value, '[toc') === FALSE) {
      return AccessResult::forbidden();
    }

    // Since entities (ie node) are cached we need to pass the current node's
    // body through it's filters and see if a TOC is being generated and
    // displayed in this block.
    /** @var \Drupal\toc_api\TocManagerInterface $toc_manager */
    $toc_manager = \Drupal::service('toc_api.manager');

    // Reset removes any stored references to a current toc.
    $toc_manager->reset($this->getCurrentTocId());

    // Reprocess the node's body since the processed result is typically
    // cached via entity render caching.
    // This will create an identical TOC instance stored in the TocManager.
    check_markup($node->body->value, $node->body->format, $node->body->getLangCode());

    return parent::blockAccess($account);
  }

}
