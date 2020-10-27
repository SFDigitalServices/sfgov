<?php

use Drupal\node\NodeInterface;

function sfgovpl_preprocess_page(&$variables) {

// The following tests the error messages.

//  $messenger = Drupal::messenger();
//  $messenger->addError('Error occurred');
//  $messenger->addMessage('Info message');
//  $messenger->addWarning('Warning message');
//  $messenger->addStatus('Status message');

}

/**
 * Implements theme_preprocess_HOOK().
 */
function sfgovpl_preprocess_page__node__campaign(&$variables) {
  // Conditionally render Logo or Title.
  if (isset($variables['node']) && $variables['node'] instanceof NodeInterface) {
    $node = $variables['node'];

    if ($node->field_logo->entity) {
      $variables['campaign_branding'] = $node->field_logo->view([
        'type' => 'image',
        'label' => 'hidden',
        'settings' => array(
          'image_style' => 'campaign_logo',
        ),
      ]);
    }
    else {
      $variables['campaign_branding'] = [
        '#markup' => '<h1>' . $node->label() . '</h1>',
      ];
    }
  }
}
