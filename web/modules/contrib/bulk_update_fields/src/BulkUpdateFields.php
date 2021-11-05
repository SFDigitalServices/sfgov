<?php

namespace Drupal\bulk_update_fields;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * BulkUpdateFields.
 */
class BulkUpdateFields {

  /**
   * {@inheritdoc}
   */
  public static function processDate($date, $date_type) {
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    //date or datetime (cannot believe this isnt handled!)
    if ($date_type == 'date') {
      $date = $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
    }
    // its datetime. (others?)
    else {
     $date = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    }

    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public static function processField($value, $field_definition) {
    // see if datetime, daterange
    if (strpos($field_definition->getType(), 'date') !== false) {
      $datetime_type = $field_definition->getFieldStorageDefinition()->getSettings()['datetime_type'];
      $value['value'] = self::processDate($value['value'], $datetime_type);
      if ($field_definition->getType() == 'daterange') {
        $value['end_value'] = self::processDate($value['end_value'], $datetime_type);
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function preprocessField($field_value) {
    // not sure if this is still valid but leaving in case
    if (isset($field_value['target_id'][0])) {
      $field_value = $field_value['target_id'];
    }
    // this caused a failure in core/entity/plugin/datatype/entityreference. removing.
    if (isset($field_value['add_more'])) {
      unset($field_value['add_more']);
    }
    // this occurs in fields like office hours.
    if (isset($field_value['value'])) {
      $field_value = $field_value['value'];
    }

    return $field_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function updateFields($entity, $fields, &$context) {
    $message = 'Updating Fields on ';
    $results_entities = [];
    $results_fields = [];
    $update = FALSE;
    foreach ($fields as $field_name => $field_value) {
      if ($entity->hasField($field_name)) {
        $field_value = self::preprocessField($field_value);
        $field_definition = $entity->get($field_name)->getFieldDefinition();
        foreach ($field_value as $key => $value) {
          if ($value == $field_name ) { continue; } // this is the case for field images for some reason
          if (!is_array($value)) { continue; } // some objects returned
          $value = self::processField($value, $field_definition);
          if (is_array($value) && isset($value['subform']) && isset($value['paragraph_type'])) {
            $paragraph = Paragraph::create(['type' => $value['paragraph_type']]);
            foreach ($value['subform'] as $p_field_name => $p_field_value) {
              if ($paragraph->hasField($p_field_name)) {
                $p_field_value = self::preprocessField($p_field_value);
                $p_field_definition = $paragraph->get($p_field_name)->getFieldDefinition();
                foreach ($p_field_value as $p_key => $p_value) {
                  if ($p_value == $p_field_name ) { continue; } // this is the case for field images for some reason
                  if (!is_array($p_value)) { continue; } // some objects returned
                  $p_field_value[$p_key] = self::processField($p_value, $p_field_definition);
                }
              }
              $paragraph->get($p_field_name)->setValue($p_field_value);
            }
            $paragraph->save();
            $value = $paragraph;
          }
          $field_value[$key] = $value;
        }
        $entity->get($field_name)->setValue($field_value);
        $update = TRUE;
        if (!in_array($field_name, $results_fields)) {
          $results_fields[] = $field_name;
        }
      }
    }
    if ($update) {
      // setNewRevision method exists on user but throws an error if called.
      // TODO?: Do other entity types need revisions set?
      if ($entity->getEntityTypeId() == 'node' && method_exists($entity, 'setNewRevision')) {
        $entity->setNewRevision();
      }
      $entity->save();
    }
    $context['results'][] = $entity->id() . ' : ' . $entity->label();
    $context['message'] = $message . $entity->label();
    $context['results']['results_entities'][] = $entity->id();
    $context['results']['results_fields'] = $results_fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bulkUpdateFieldsFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message_field = \Drupal::translation()->formatPlural(
        count($results['results_fields']),
        'One field processed', '@count fields processed'
      );
      $message_entity = \Drupal::translation()->formatPlural(
        count($results['results_entities']),
        'One entity', '@count entities'
      );
      $message = $message_field.' on '.$message_entity;
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addStatus($message);
  }

}
