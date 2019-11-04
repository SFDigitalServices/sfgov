<?php

/**
 * Implements template_preprocess_paragraph().
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An array of elements to display in view mode.
 *   - paragraph: The paragraph object.
 *   - view_mode: View mode; e.g., 'full', 'teaser'...
 */
function sfgovpl_preprocess_paragraph__form(&$variables) {
    $globalSettings = \Drupal::service('settings');
    $formBuilderUrl = $globalSettings->get('formbuilder_include_url', false);
    if ($formBuilderUrl) {
        // normalize url by removing trailing /
        if (substr($formBuilderUrl, -1) == "/") {
            $formBuilderUrl = substr_replace($formBuilderUrl, "", -1);
        }
        
        $variables['settings'] = array(
            'formbuilder_include_url' => $formBuilderUrl,
        );

    }
}

function sfgovpl_preprocess_paragraph(&$variables) {
    $paragraph = $variables['paragraph'];
    $paragraph_type = $paragraph->getType();
    if($paragraph_type == 'process_step') {
        $variables['sbsId'] = $paragraph->getParentEntity()->id();
        $referencedEntities = $paragraph->get('field_transaction')->referencedEntities();
        if(count($referencedEntities) > 0) {
            $txId = $referencedEntities[0]->id();
            $variables['txId'] = $txId;
        }
    }
}