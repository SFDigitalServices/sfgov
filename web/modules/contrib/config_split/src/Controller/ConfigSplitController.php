<?php

namespace Drupal\config_split\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Class ConfigSplitController.
 *
 * @package Drupal\config_split\Controller
 */
class ConfigSplitController extends ControllerBase {

  /**
   * Enable the split.
   *
   * @param string $config_split
   *   The split name.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function enableEntity($config_split) {
    $entity = $this->entityTypeManager()->getStorage('config_split')->load($config_split);
    $entity->set('status', TRUE);
    $entity->save();

    return $this->redirect('entity.config_split.collection');
  }

  /**
   * Disable the split.
   *
   * @param string $config_split
   *   The split name.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function disableEntity($config_split) {
    $entity = $this->entityTypeManager()->getStorage('config_split')->load($config_split);
    $entity->set('status', FALSE);
    $entity->save();

    return $this->redirect('entity.config_split.collection');
  }

}
