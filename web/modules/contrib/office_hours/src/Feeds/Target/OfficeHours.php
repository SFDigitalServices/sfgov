<?php

namespace Drupal\office_hours\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a office_hours field mapper.
 *
 * @FeedsTarget(
 *   id = "office_hours_feeds_target",
 *   field_types = {"office_hours"}
 * )
 */
class OfficeHours extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition);
    if ($field_definition->getType() === 'office_hours') {
      $definition
        ->addProperty('day')
        ->addProperty('starthours')
        ->addProperty('endhours')
        ->addProperty('comment');
    }
    return $definition;
  }

}
