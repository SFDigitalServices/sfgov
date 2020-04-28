<?php

namespace Drupal\fixed_block_content\Normalizer;

use Drupal\hal\Normalizer\ContentEntityNormalizer;

/**
 * Block content normalizer to store as fixed content block default value.
 */
class BlockContentNormalizer extends ContentEntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\block_content\Entity\BlockContent';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $attributes = parent::normalize($object, $format, $context);
    if (isset($context['fixed_block_content'])) {
      // Remove local entity IDs.
      $ids = [
        'id',
        'revision_id',
        'uuid',
        'changed',
        'revision_created',
        'revision_user',
      ];
      foreach ($ids as $id) {
        unset($attributes[$id]);
      }
    }

    return $attributes;
  }

}
