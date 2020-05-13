<?php

use Drupal\node\NodeInterface;
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

  // Inject campaign theme into paragraph campaign_spotlight and campaign_resources
  $parent = $paragraph->getParentEntity();
  if (isset($parent)
    && $parent instanceof NodeInterface
    && $parent->bundle() == 'campaign') {
    /** @var \Drupal\taxonomy\Entity\Term $theme */
    $theme = $parent->get('field_campaign_theme')->entity;

    if ($paragraph_type == 'campaign_spotlight') {
      $background_color = $theme->get('field_primary_color')->getValue();
      $background_color_value = $background_color[0]['color'];
    } elseif ($paragraph_type == 'campaign_resources') {
      $background_color = $theme->get('field_accent_l1')->getValue();
      $background_color_value = $background_color[0]['color'];
    } elseif ($paragraph_type == 'accordion') {
      $background_color = $theme->get('field_accent_l1')->getValue();
      $variables['group_right_background'] = $background_color[0]['color'];
    }

    if (isset($background_color_value)) {
      $variables['attributes']['style'] = "background-color: {$background_color_value}";
    }

    // TODO: detect parent paragraph, instead of parent node.
    if($paragraph_type == 'link') {
      $variables['content']['field_link']['#title'] = t('Parent Program');
    }
  }
}

/**
 * Implements hook_preprocess_paragraph().
 */
function sfgovpl_preprocess_paragraph__instagram_embed(&$variables) {
  $paragraph = $variables['paragraph'];
  $variables['#attached']['library'][] = 'sfgovpl/sfgov-instagram-feed';
  $variables['#attached']['drupalSettings']['sfgov']['instagram_feed'][$paragraph->id()] = [
    'paragraph_id' => $paragraph->id(),
    'instagram_profile' => $paragraph->field_instagram_profile->value,
  ];
}