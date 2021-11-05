<?php

namespace Drupal\viewfield;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service Provider for Viewfield
 */
class ViewfieldServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['hal'])) {
      // Hal module is enabled, add our new normalizer for viewfield items.
      $service_definition = new Definition('Drupal\viewfield\Normalizer\ViewfieldNormalizer', array(
        new Reference('hal.link_manager'),
        new Reference('serializer.entity_resolver'),
      ));
      $service_definition->addTag('normalizer', array('priority' => 20));
      $container->setDefinition('serializer.normalizer.viewfield_item', $service_definition);
    }
  }

}