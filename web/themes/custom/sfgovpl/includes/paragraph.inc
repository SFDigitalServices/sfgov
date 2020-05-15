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

  $variables['captions'] = $video_service->getYoutubeTranscript($video_id);

  // Link transcript
  $url = Url::fromUserInput('/video/' . $video_id);
  $link = Link::fromTextAndUrl(t('Open in another tab'), $url);
  $link = $link->toRenderable();
  $link['#attributes']['target'] = '_blank';
  $variables['link_transcript'] = $link;
}
