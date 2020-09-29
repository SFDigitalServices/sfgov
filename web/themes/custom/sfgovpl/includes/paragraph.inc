<?php

use Drupal\node\NodeInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

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
  $preprocess = __FUNCTION__ . '__' . $paragraph_type;
  if (function_exists($preprocess)) {
    $preprocess($variables);
  }

  if($paragraph_type == 'process_step') {
      $variables['sbsId'] = $paragraph->getParentEntity()->id();
      $referencedEntities = $paragraph->get('field_transaction')->referencedEntities();
      if(count($referencedEntities) > 0) {
          $txId = $referencedEntities[0]->id();
          $variables['txId'] = $txId;
      } 
  }
  
  if($paragraph_type == 'mailing_address') {
      // Get node info for use in twig templates
      $node = \Drupal::request()->attributes->get('node');
      $variables['nodetype'] = $node->getType();
  }

  // Inject campaign theme into paragraph campaign_spotlight and campaign_resources
  $parent = $paragraph->getParentEntity();
  if (isset($parent)
    && $parent instanceof NodeInterface
    && $parent->bundle() == 'campaign') {
    /** @var \Drupal\taxonomy\Entity\Term $theme */
    $theme = $parent->get('field_campaign_theme')->entity;
    if ($paragraph_type == 'campaign_spotlight' || $paragraph_type == 'image_with_text') {
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
    if ($paragraph_type == 'link') {
      $variables['content']['field_link']['#title'] = t('Parent Program');
    }
  }

  // Render image attirubutes.
  if (function_exists('_sfgov_image_attributes_renderable')) {
    if (isset($parent) && $parent->bundle() == 'section' && $paragraph_type == 'campaign') {
      if ($sections = $parent->get('field_content')->referencedEntities()) {
        $section = reset($sections);
        $media = $section->get('field_media')->referencedEntities();
        if ($entity = reset($media)) {
          $element = _sfgov_image_attributes_renderable($entity);
        }
      }
      $variables['field_media_image_attributes'] = isset($element) ? $element : '';
    }
  }
}

/**
 * Implements hook_preprocess_paragraph().
 */
function sfgovpl_preprocess_paragraph__instagram_embed(&$variables) {
  $paragraph = $variables['paragraph'];
  $parent_field_name = $paragraph->parent_field_name->value;
  $items = 12;
  $items_per_row = 4;
  $image_size = 150;
  $styling = TRUE;

  if($parent_field_name == 'field_contents') {
    $items = 9;
    $items_per_row = 3;
    $image_size = 480;
    $styling = FALSE;
  }

  $variables['#attached']['library'][] = 'sfgovpl/sfgov-instagram-feed';
  $variables['#attached']['drupalSettings']['sfgov']['instagram_feed'][$paragraph->id()] = [
    'paragraph_id' => $paragraph->id(),
    'instagram_profile' => $paragraph->field_instagram_profile->value,
    'items' => $items,
    'items_per_row' => $items_per_row,
    'image_size' => $image_size,
    'styling' => $styling,
  ];
}

/**
 * Implements hook_preprocess_paragraph().
 */
function sfgovpl_preprocess_paragraph__twitter_embed(&$variables) {
  $variables['#attached']['library'][] = 'sfgovpl/sfgov-twitter-feed';
}

/**
 * Preprocess paragraph video.
 *
 * @param $variables
 */
function sfgovpl_preprocess_paragraph__video(&$variables) {
  $paragraph = $variables['paragraph'];
  $video_url = $paragraph->get('field_video')->value;

  /** @var \Drupal\sfgov_video\VideoService $video_service */
  $video_service = \Drupal::service('sfgov_video.utilities');
  $video_id = $video_service->getVideoId($video_url);

  // Link transcript
  $url = Url::fromRoute('sfgov_video.video_controller_page', [
    'video_id' => $video_id,
    'paragraph_id' => $paragraph->id()
  ]);
  $link = Link::fromTextAndUrl(t('Open in another tab'), $url);
  $link = $link->toRenderable();
  $link['#attributes']['target'] = '_blank';
  $variables['link_transcript'] = $link;
}

/**
 * Preprocess paragraph document.
 *
 * @param $variables
 */
function sfgovpl_preprocess_paragraph__document(&$variables) {
  // Don't translate document paragraphs by gtranslate.
  $variables['attributes']['class'][] = 'notranslate';
}
