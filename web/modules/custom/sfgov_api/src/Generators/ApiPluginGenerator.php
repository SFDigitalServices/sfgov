<?php

namespace Drupal\sfgov_api\Generators;

use DrupalCodeGenerator\Command\ModuleGenerator;
use DrupalCodeGenerator\Utils;

/**
 * Generate a custom sfgov_api plugin.
 */
class ApiPluginGenerator extends ModuleGenerator {

  protected string $name = 'custom:api-plugin';
  protected string $description = 'Generates an sfgov API plugin';
  protected string $templatePath = 'modules/custom/sfgov_api/src/Templates';

  private array $entityTypes = [
    'node' => 'Node',
    'paragraph' => 'Paragraph',
    'media' => 'Media',
  ];

  /**
   * {@inheritdoc}
   */
  protected function generate(array &$vars): void {
    $vars['machine_name'] = 'sfgov_api';
    $vars['entity_type'] = $this->choice('Plugin entity type', $this->entityTypes, 'node', FALSE);
    $vars['bundle'] = $this->ask('Bundle machine name');
    $vars['use_helper'] = $this->confirm('Use helper trait?');
    $vars['generate_fields'] = $this->confirm('Add all fields from the entity?');

    $vars['bundle_camelize'] = Utils::camelize($vars['bundle']);
    $vars['entity_type_ucfirst'] = ucfirst($vars['entity_type']);

    $entityTypeBundleInfo = \Drupal::service('entity_type.bundle.info');
    $bundle_list = $entityTypeBundleInfo->getBundleInfo($vars['entity_type']);
    // Check if the bundle exists for the specified entity type.
    $vars['bundle_exists'] = in_array($vars['bundle'], array_keys($bundle_list));

    if ($vars['generate_fields'] && $vars['bundle_exists']) {
      $entityFieldManager = \Drupal::service('entity_field.manager');
      $fields = $entityFieldManager->getFieldDefinitions($vars['entity_type'], $vars['bundle']);

      $vars['entity_fields'] = [];
      foreach ($fields as $field_name => $field_definition) {
        $vars['entity_fields'][] = $field_name;
      }
    }

    $this->addFile('src/Plugin/SfgovApi/{entity_type_ucfirst}/{bundle_camelize}.php', 'PluginTemplate.twig');
  }

}
