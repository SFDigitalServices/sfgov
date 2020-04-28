<?php

namespace Drupal\group;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\group\Access\LatestRevisionCheck;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider for the Group module.
 *
 * This is used to alter the content moderation services for integration with
 * the group module. This can't be done via a normal service declaration as
 * decorating optional services is not supported.
 */
class GroupServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['content_moderation'])) {
      // Decorate the latest revision access check.
      $latest_revision_definition = new Definition(LatestRevisionCheck::class, [
        new Reference('group.latest_version_access.inner'),
        new Reference('entity_type.manager'),
      ]);
      $latest_revision_definition->setPublic(TRUE);
      $latest_revision_definition->setDecoratedService('access_check.latest_revision');
      $container->setDefinition('group.latest_version_access', $latest_revision_definition);

      // Decorate the state transition validation service.
      $state_transition_definition = new Definition(StateTransitionValidation::class, [
        new Reference('group.state_transition_validation.inner'),
        new Reference('content_moderation.moderation_information'),
        new Reference('current_route_match'),
        new Reference('entity_type.manager'),
        new Reference('plugin.manager.group_content_enabler'),
      ]);
      $state_transition_definition->setPublic(TRUE);
      $state_transition_definition->setDecoratedService('content_moderation.state_transition_validation');
      $container->setDefinition('group.state_transition_validation', $state_transition_definition);
    }
  }

}
