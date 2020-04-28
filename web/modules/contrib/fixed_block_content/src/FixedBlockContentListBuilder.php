<?php

namespace Drupal\fixed_block_content;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Fixed block content list handler.
 *
 * @see \Drupal\fixed_block_content\FixedBlockContentInterface
 */
class FixedBlockContentListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header_row = [
      'id' => $this->t('ID'),
      'label' => $this->t('Block description'),
      'block_content_bundle' => $this->t('Block type'),
      'block_content' => $this->t('Block content'),
    ];
    return $header_row + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\fixed_block_content\FixedBlockContentInterface $entity */
    $block_content = $entity->getBlockContent(FALSE);

    $row = [
      'id' => $entity->id(),
      'label' => $entity->label(),
      'block_content_bundle' => $entity->getBlockContentBundle(),
      'block_content' => $block_content ? $block_content->toLink(NULL, 'canonical', ['query' => \Drupal::destination()->getAsArray()]) : '-',
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Get back after fixed block edition.
    if (isset($operations['edit'])) {
      $operations['edit']['query']['destination'] = $entity->toUrl('collection')->toString();
    }

    // Adds the import/export operations.
    $operations['export'] = [
      'title' => $this->t('Restore default content'),
      'weight' => 20,
      'url' => $entity->toUrl('export-form'),
    ];
    $operations['import'] = [
      'title' => $this->t('Set contents as default'),
      'weight' => 20,
      'url' => $entity->toUrl('import-form'),
    ];

    return $operations;
  }

}
