<?php

namespace Drupal\simple_sitemap_engines\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\simple_sitemap\Form\FormHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Search engine entity list builder.
 */
class SearchEngineListBuilder extends ConfigEntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * SearchEngineListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['url'] = $this->t('Submission URL');
    $header['variants'] = $this->t('Sitemap variants');
    $header['last_submitted'] = $this->t('Last submitted');

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    /** @var \Drupal\simple_sitemap_engines\Entity\SearchEngine $entity */
    $row['label'] = $entity->label();
    $row['url'] = $entity->url;
    $row['variants'] = implode(', ', $entity->sitemap_variants);
    $row['last_submitted'] = $entity->last_submitted
      ? $this->dateFormatter->format($entity->last_submitted, 'short')
      : $this->t('Never');

    return $row;
  }

  public function render() {
    return ['simple_sitemap_engines' => [
      '#prefix' => FormHelper::getDonationText(),
      '#title' => $this->t('Submission status'),
      '#type' => 'fieldset',
      'table' => parent::render(),
    ]];
  }

}
