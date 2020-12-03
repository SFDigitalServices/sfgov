<?php 

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
  return $info;
}

/**
 * Implements hook_tokens().
 */
function  sfgov_utilities_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if($type == 'sfgov') {
    foreach($tokens as $name => $original) {
      switch($name) {
        case 'logged-in':
          $replacements[$original] = \Drupal::currentUser()->isAnonymous() ? "no" : "yes";
          break;
      }
    }
  }
  return $replacements;
}
