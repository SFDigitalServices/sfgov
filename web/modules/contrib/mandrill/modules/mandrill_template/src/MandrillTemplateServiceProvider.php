<?php

/**
 * @file
 * Contains Drupal\mandrill_template\MandrillTemplateServiceProvider
 */

namespace Drupal\mandrill_template;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the Mandrill service to allow templated emails to be sent.
 */
class MandrillTemplateServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('mandrill.service');
    $definition->setClass('Drupal\mandrill_template\MandrillTemplateService');
  }

}
