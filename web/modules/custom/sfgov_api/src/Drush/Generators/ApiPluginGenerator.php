<?php

namespace Drupal\sfgov_api\Drush\Generators;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use DrupalCodeGenerator\Asset\AssetCollection as Assets;
use DrupalCodeGenerator\Attribute\Generator;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\GeneratorType;
use DrupalCodeGenerator\Utils;
use Drush\Commands\AutowireTrait;

/**
 * Generates an sfgov API plugin.
 */
#[Generator(
    name: 'sfgov:api-plugin',
    description: 'Generates an sfgov API plugin',
    aliases: ['sfgap'],
    templatePath: __DIR__,
    type: GeneratorType::MODULE_COMPONENT,
)]
class ApiPluginGenerator extends BaseGenerator {

  use AutowireTrait;

  /**
   * Inject dependencies into the Generator.
   */
  public function __construct(
        protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
        protected EntityFieldManagerInterface $entityFieldManager,
    ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function generate(array &$vars, Assets $assets): void {
    $ir = $this->createInterviewer($vars);
    $vars['machine_name'] = 'sfgov_api';
    $vars['entity_type'] = $ir->choice('Plugin entity type', [
      'node' => 'Node',
      'paragraph' => 'Paragraph',
      'media' => 'Media',
    ], 'node', FALSE);
    $vars['bundle'] = $ir->ask('Bundle machine name');
    $vars['use_helper'] = $ir->confirm('Use helper trait?');
    $vars['generate_fields'] = $ir->confirm('Add all fields from the entity?');

    $vars['bundle_camelize'] = Utils::camelize($vars['bundle']);
    $vars['entity_type_ucfirst'] = ucfirst($vars['entity_type']);

    $bundle_list = $this->entityTypeBundleInfo->getBundleInfo($vars['entity_type']);
    // Check if the bundle exists for the specified entity type.
    $vars['bundle_exists'] = in_array($vars['bundle'], array_keys($bundle_list));

    if ($vars['generate_fields'] && $vars['bundle_exists']) {
      $fields = $this->entityFieldManager->getFieldDefinitions($vars['entity_type'], $vars['bundle']);

      $vars['entity_fields'] = [];
      foreach ($fields as $field_name => $field_definition) {
        $vars['entity_fields'][] = $field_name;
      }
    }

    $assets->addFile('src/Plugin/SfgApi/{entity_type_ucfirst}/{bundle_camelize}.php', 'PluginTemplate.twig');
  }

}
