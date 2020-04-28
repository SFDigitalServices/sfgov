<?php

namespace Drupal\fixed_block_content;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\hal\LinkManager\LinkManager;
use Drupal\Core\Entity\EntityTypeInterface;
use Psr\Log\LoggerInterface;
use Drupal\block_content\Entity\BlockContent;

/**
 * Fixed block content default content handler.
 *
 * @see \Drupal\fixed_block_content\FixedBlockContentInterface
 */
class DefaultContentHandler implements DefaultContentHandlerInterface {

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Injected cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The HAL link manager.
   *
   * @var \Drupal\hal\LinkManager\LinkManager
   */
  protected $linkManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Creates a new DefaultContentHandler object.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\hal\LinkManager\LinkManager $link_manager
   *   The HAL link manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(Serializer $serializer, CacheBackendInterface $cache, LinkManager $link_manager, LoggerInterface $logger) {
    $this->serializer = $serializer;
    $this->cache = $cache;
    $this->linkManager = $link_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('serializer'),
      $container->get('cache.default'),
      $container->get('hal.link_manager'),
      $container->get('logger.factory')->get('fixed_block_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function exportDefaultContent(FixedBlockContentInterface $fixed_block) {
    if (empty($fixed_block->get('default_content'))) {
      return NULL;
    }

    $new_block_content = NULL;
    try {
      // Set the internal link domain.
      // @todo take this value from config
      $this->linkManager->setLinkDomain('http://fixed_block_content.drupal.org');
      // Invalidates type links cache.
      // @todo remove when #2928882 is solved
      $this->cache->invalidate('hal:links:types');
      $new_block_content = $this->serializer->deserialize($fixed_block->get('default_content'), BlockContent::class, 'hal_json', ['fixed_block_content' => $fixed_block]);
    }
    catch (\Exception $e) {
      try {
        $message = $e->getMessage();
        // Earlier versions stored the default content in plain JSON (not HAL).
        $new_block_content = $this->serializer->deserialize($fixed_block->get('default_content'), BlockContent::class, 'json', ['fixed_block_content' => $fixed_block]);
      }
      catch (\Exception $e) {
        // Deserialization fails.
        $this->logger->error('Unable to export default content for fixed block %id, deserialization fails with message "%message"', ['%id' => $fixed_block->id(), '%message' => $message]);
      }
    }

    // Restore HAL link domain to default.
    $this->linkManager->setLinkDomain('');
    // Invalidates type links cache.
    // @todo remove when #2928882 is solved
    $this->cache->invalidate('hal:links:types');

    return $new_block_content;
  }

  /**
   * {@inheritdoc}
   */
  public function importDefaultContent(FixedBlockContentInterface $fixed_block) {
    // Set the internal link domain.
    // @todo take this value from config
    $this->linkManager->setLinkDomain('http://fixed_block_content.drupal.org');
    $fixed_block->set('default_content', $this->serializer->serialize($fixed_block->getBlockContent(), 'hal_json', ['fixed_block_content' => $fixed_block]));
    // Restore HAL link domain to default.
    $this->linkManager->setLinkDomain('');
  }

}
