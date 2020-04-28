<?php

/**
 * @file
 * Contains \Drupal\mandrill_template\Controller\MandrillTemplateMapListBuilder.
 */

namespace Drupal\mandrill_template\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of MandrillTemplateMap entities.
 *
 * @ingroup mandrill_template
 */
class MandrillTemplateMapListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['template_id'] = $this->t('Mandrill Template');
    $header['main_section'] = $this->t('Primary Content Zone');
    $header['mailsystem_key'] = $this->t('In Use By');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\mandrill_template\Entity\MandrillTemplateMap */
    $row['label'] = $entity->label() . ' (Machine name: ' . $entity->id() . ')';
    $row['template_id'] = $entity->template_id;
    $row['main_section'] = $entity->main_section;
    $row['mailsystem_key'] = $entity->mailsystem_key;

    return $row + parent::buildRow($entity);
  }

}
