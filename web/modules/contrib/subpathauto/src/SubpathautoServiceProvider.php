<?php

namespace Drupal\subpathauto;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Update\UpdateKernel;

/**
 * Defines a service provider for the Subpathauto module.
 */
class SubpathautoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // The alias-based processor requires the path_alias entity schema to be
    // installed, so we prevent it from being registered to the path processor
    // manager. We do this by removing the tags that the compiler pass looks
    // for. This means that the URL generator can safely be used during the
    // database update process.
    if ($container->get('kernel') instanceof UpdateKernel && $container->hasDefinition('path_processor_subpathauto')) {
      $container->getDefinition('path_processor_subpathauto')
        ->clearTag('path_processor_inbound')
        ->clearTag('path_processor_outbound');
    }
  }

}
