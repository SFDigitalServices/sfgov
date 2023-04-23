<?php

use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\sfgov_utilities\Utility;

// field_step_online
// field_step_phone
// field_step_in_person
// email
// mail
// other_title ---\
//                 \___ grouped together
// other ----------/


try {

  /**
   * get allowed paragraph types for field steps
   */
  $entity_type_id = 'node';
  $field_name = 'field_step_online'; // this is ok, since all the field steps have the same allowed paragraph types

  // Load the field storage configuration.
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName($entity_type_id, $field_name);

  // Check if the field storage configuration exists.
  if ($field_storage) {
    // Get the field definition.
    $field_definition = \Drupal\field\Entity\FieldConfig::loadByName($entity_type_id, 'transaction', $field_name);

    // Get the allowed entity types.
    $allowed_paragraph_types = $field_definition->getSetting('handler_settings')['target_bundles'];
  }

  /**
   * get allowed paragraph types for step field, field_content
   */

  $entity_type_id = 'paragraph';
  $field_name = 'field_content'; // this is ok, since all the field steps have the same allowed paragraph types

  // Load the field storage configuration.
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName($entity_type_id, $field_name);

  // Check if the field storage configuration exists.
  if ($field_storage) {
    // Get the field definition.
    $field_definition = \Drupal\field\Entity\FieldConfig::loadByName($entity_type_id, 'step', $field_name);

    // Get the allowed entity types.
    $allowed_step_paragraph_types = $field_definition->getSetting('handler_settings')['target_bundles'];
  }

  // print_r($allowed_paragraph_types);
  // print_r($allowed_step_paragraph_types);

  // start with some base things
  $baseTxItem = [];
  $baseTxItem["online"] = "";
  $baseTxItem['phone'] = "";
  $baseTxItem['in-person'] = "";
  $baseTxItem['email'] = "";
  $baseTxItem['mail'] = "";
  $baseTxItem['other'] = "";

  // add all the other stuff
  foreach ($baseTxItem as $txFieldKey => $value) {
    foreach ($allowed_paragraph_types as $txFieldParagraphTypeKey => $value) {
      $baseTxItem[$txFieldKey . "_" . $txFieldParagraphTypeKey] = "";
      if ($txFieldParagraphTypeKey == "step") {
        foreach ($allowed_step_paragraph_types as $txFieldParagraphTypePargraphTypeKey => $value) {
          $baseTxItem[$txFieldKey . "_step_" . $txFieldParagraphTypePargraphTypeKey] = "";
        }
      }
    }
  }

  $baseTxItem['_nid'] = "";
  $baseTxItem['_title'] = "";
  $baseTxItem['_url'] = "";
  $baseTxItem['_published'] = "";

  ksort($baseTxItem);

  // print_r($baseTxItem);

  $txNodes = Utility::getNodes('transaction'); // get all english published and unpublished

  $txs = [];

  foreach ($txNodes as $tx) {
    // if ($tx->id() == 3) {
      $txItem = $baseTxItem;
      $txItem["_nid"] = $tx->id();
      $txItem["_title"] = $tx->getTitle();
      $txItem["_url"] = "https://test-sfgov.pantheonsite.io/node/" . $tx->id();
  
      $online = $tx->get('field_step_online');
      checkSteps($online, $txItem);
      $phone = $tx->get('field_step_phone');
      checkSteps($phone, $txItem);
      $in_person = $tx->get('field_step_in_person');
      checkSteps($in_person, $txItem);
      $email = $tx->get('field_step_email');
      checkSteps($email, $txItem);
      $mail = $tx->get('field_step_mail');
      checkSteps($mail, $txItem);
      $other_title = $tx->get('field_step_other_title');
      $other = $tx->get('field_step_other');
      checkSteps($other, $txItem);

      $txItem['online'] = markX($online);
      $txItem['phone'] = markX($phone);
      $txItem['in-person'] = markX($in_person);
      $txItem['email'] = markX($email);
      $txItem['mail'] = markX($mail);
      $txItem['other'] = markX($other);

      $txItem["published"] = $tx->isPublished();
      $txs[] = $txItem;
    // }
  }

  echo json_encode($txs, JSON_PRETTY_PRINT);
  // echo "\n";
  // echo "count: " . count($txs) . "\n";
} catch (\Exception $e) {
  error_log($e->getMessage());
}

function markX($field) {
  return (!empty($field->getValue()) ? "X" : "");
}

function checkSteps($txField, &$arr) {
  $stepItem = [];
  $fieldLabel = strtolower($txField->getFieldDefinition()->getLabel());
  
  foreach ($txField->referencedEntities() as $txFieldReferencedEntity) {
    if (!empty($txFieldReferencedEntity)) {
      $fieldKey = $fieldLabel . "_" . $txFieldReferencedEntity->getType();
      $arr[$fieldKey] = "X";

      if ($txFieldReferencedEntity->hasField('field_content')) {
        foreach ($txFieldReferencedEntity->get('field_content')->referencedEntities() as $paragraphReferencedEntity) {
          $arr[$fieldKey . "_" . $paragraphReferencedEntity->getType()] = "X";
        }
      }
    }
  }
}
