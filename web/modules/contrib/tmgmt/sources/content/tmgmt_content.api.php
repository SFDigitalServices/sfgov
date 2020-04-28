<?php

/**
 * @file
 * Hooks provided by the content entity source module.
 */

/**
 * @addtogroup tmgmt_source
 * @{
 */

/**
 * Allows to alter $query used to list entities on specific entity type overview
 * pages.
 *
 * @see TMGMTEntityDefaultSourceUIController
 */
function hook_tmgmt_content_list_query_alter(\Drupal\Core\Entity\Query\QueryInterface $query) {
  $query->condition('type', array('article', 'page'), 'IN');
}

/**
 * Allows to exclude some fields from translation with TMGMT.
 *
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   The entity to exclude fields from.
 * @param \Drupal\Core\Field\FieldDefinitionInterface[] $translatable_fields
 *   An array of field definitions, keyed by field name.
 */
function hook_tmgmt_translatable_fields_alter(\Drupal\Core\Entity\ContentEntityInterface $entity, array &$translatable_fields) {
  if (isset($translatable_fields['title'])) {
    unset($translatable_fields['title']);
  }
}

/**
 * Any module can override the default field processor and register its own
 * class for a given field type in hook_field_info_alter() using the
 * tmgmt_field_processor key.
 *
 * The class must implement \Drupal\tmgmt_content\FieldProcessorInterface.
 *
 * @see \Drupal\tmgmt_content\DefaultFieldProcessor
 */
function hook_field_info_alter(&$info) {
  $info['metatag']['tmgmt_field_processor_class'] = '\Drupal\Acme\MetatagFieldProcessor';
}

/**
 * @} End of "addtogroup tmgmt_source".
 */
