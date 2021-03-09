<?php 

use Drupal\node\Entity\Node;

/**
 * Implements hook_token_info
 */
function sfgov_utilities_token_info() {
  $info = [];

  $info['types']['sfgov'] = [
    'name' => t('SFGov'),
    'description' => t('Custom tokens for SFGov')
  ];

  $info['tokens']['sfgov']['logged-in'] = [
    'name' => t('Current user logged in status'),
    'description' => t('The logged in status of the current user.  "yes" if logged in, "no" otherwise')
  ];

  $info['tokens']['sfgov']['field_topics'] = [
    'name' => t('Field topic titles'),
    'description' => t('Referenced topics, titles only.  Different from node:field_topics')
  ];

  $info['tokens']['sfgov']['field_departments'] = [
    'name' => t('Field related department titles'),
    'description' => t('Referenced related departments, titles only.  Different from node:field_departments')
  ];
  
  return $info;
}

/**
 * Implements hook_tokens().
 */
function sfgov_utilities_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if($type == 'sfgov') {
    foreach($tokens as $name => $original) {
      switch($name) {
        case 'logged-in':
          $replacements[$original] = \Drupal::currentUser()->isAnonymous() ? "no" : "yes";
          break;
        case 'field_topics':
          if(!empty($data['node'])) {
            $replacements[$original] = _sfgov_utilities_referenced_node_titles($data['node'], 'field_topics');
          }
          break;
        case 'field_departments':
          if(!empty($data['node'])) {
            $replacements[$original] = _sfgov_utilities_referenced_node_titles($data['node'], 'field_departments');
          }
        default:
          break;
      }
    }
  }

  return $replacements;
}

function _sfgov_utilities_referenced_node_titles($node, $fieldName) {
  if(empty($node)) return;
  if(!$node->hasField($fieldName)) return;

  $fieldNodes = $node->get($fieldName)->getValue();
  $titles = [];

  foreach($fieldNodes as $fieldNode) {
    $referencedNode = Node::load($fieldNode['target_id']);
    if($referencedNode) {
      $titles[] = $referencedNode->getTitle();
    }
  }

  return json_encode($titles);
}
