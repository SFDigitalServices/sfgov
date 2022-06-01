<?php

use Drupal\Core\Render\Element;
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
/**
 * Implements template_preprocess_paragraph().
 */
function sfgovpl_preprocess_paragraph__process_step(&$variables) {
  $paragraph = $variables['paragraph'];
  $variables['sbsId'] = $paragraph->getParentEntity()->id();
  $referencedEntities = $paragraph->get('field_transaction')->referencedEntities();
  if (count($referencedEntities) > 0) {
    $txNode = $referencedEntities[0];
    $txId = $txNode->id();
    // transaction id and url will be used
    // as query parameters to conditionally display the step by step reference
    // in the linked transaction
    $variables['txId'] = $txId;
    $variables['txUrl'] = $txNode->toUrl('canonical', [
      'language' => \Drupal::languageManager()->getCurrentLanguage(),
    ])->toString();
  }
}

function sfgovpl_preprocess_paragraph(&$variables) {
  $paragraph = $variables['paragraph'];
  $paragraph_type = $paragraph->getType();
  $preprocess = __FUNCTION__ . '__' . $paragraph_type;
  if (function_exists($preprocess)) {
    $preprocess($variables);
  }

  if($paragraph_type == 'mailing_address') {
      // Get node info for use in twig templates
      $node = \Drupal::request()->attributes->get('node');
      $variables['nodetype'] = $node->getType();
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
    if ($paragraph_type == 'image_with_text') {
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

  // Our variables to start our document information list variable.
  $paragraph = $variables['paragraph'];
  $entity_type_manager = \Drupal::entityTypeManager();
  $media_manager = $entity_type_manager->getStorage('media');
  $file_manager = $entity_type_manager->getStorage('file');
  $generator_service = \Drupal::service('file_url_generator');

  // Create a listing of all the URLS and labels of all the attached documents.
  $variables['document_list'] = [];
  if ($paragraph->hasField('field_file')) {
    foreach ($paragraph->get('field_file')->getValue() as $delta => $item) {
      // If the field returns any media entities (aka not empty)...
      if ($media_item = $media_manager->load($item['target_id'])) {

        // Get the file uri of the file attached to the media entity.
        $file_item = $media_item->get('field_media_file')->getValue();
        $file = $file_manager->load($file_item[0]['target_id']);
        if ($file && $file_uri = $file->getFileUri()) {
          // The variable "document_list" is used in paragraph--document.html.twig.
          // We generate a relative url to the file.
          $variables['document_list'][$delta] = [
            'label' => $media_item->label(),
            'url' => $generator_service->generateString($file_uri),
          ];
        }
      };
    }
  }

}

/**
 * Preprocess paragraph section.
 *
 * @param $variables
 */
function sfgovpl_preprocess_paragraph__section(&$variables) {
  /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
  $paragraph = $variables['paragraph'];

  // Check if we are within a resource_collection node.
  $parent = $paragraph->getParentEntity();
  if (!($parent instanceof NodeInterface) || $parent->bundle() !== "resource_collection") {
    return;
  }

  if ($variables['view_mode'] !== "resource_collection") {
    return;
  }

  // Swap the view_mode for all document paragraphs.
  foreach (Element::getVisibleChildren($variables['content']['field_content']) as $key) {
    /** @var \Drupal\paragraphs\ParagraphInterface $child */
    $child = $variables['content']['field_content'][$key]['#paragraph'];
    if ($child->bundle() !== "document") {
      continue;
    }

    // We use a different view mode here so that old content still works.
    $variables['content']['field_content'][$key]['#view_mode'] = 'resource_collection_multiple';
  }
}

/**
 * Implements hook_preprocess_paragraph().
 */
function sfgovpl_preprocess_paragraph__powerbi_embed(&$variables) {
  /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
  $paragraph = $variables['paragraph'];

  // Format data source link.
  if ($data_source = $paragraph->get('field_source_data')->value) {
    $variables['content']['field_source_data'] = [
      '#type' => 'inline_template',
      '#weight' => -10,
      '#template' => '<p><a href="{{ url }}" target="_blank">{{ "View source data"}}</a></p>',
      "#context" => [
        'url' => $data_source,
      ],
    ];
  }

  if ($paragraph->get('field_data_notes')->value && !isset($variables['content']['field_data_notes']['#link_embedded'])) {
    $variables['content']['field_data_notes'][0] = [
      $variables['content']['field_source_data'],
      $variables['content']['field_data_notes'][0],
    ];
    $variables['content']['field_data_notes']['#link_embedded'] = TRUE;
  }

  // Calculate aspect ratios.
  $variables['charts'] = [];
  foreach (['desktop', 'mobile'] as $device) {
    $variables['charts'][$device]['embed_url'] = $paragraph->get("field_{$device}_embed_url")->value;
    $variables['charts'][$device]['padding_top'] = '100%';
    if (($device_width = $paragraph->get("field_{$device}_width")->value) && ($device_height = $paragraph->get("field_{$device}_height")->value)) {
      $variables['charts'][$device]['padding_top'] = ((int) $device_height / (int) $device_width) * 100;
    }
  }

  // Add kbd instructions.
  $variables['kbd_instructions'] = NULL;

  if ($kbd_instructions = \Drupal::config('sfgov_media.settings')->get('powerbi_kbd_instructions')) {
    $variables['kbd_instructions'] = \Drupal\Core\Render\Markup::create($kbd_instructions);
  }

}

/**
 * Implements template_preprocess_paragraph() for `resource node` paragraphs.
 */
function sfgovpl_preprocess_paragraph__resource_node(&$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $outer */
  $paragraph = $variables['paragraph'];
  if ($paragraph->hasField('field_node') && NULL !== $paragraph->get('field_node')[0]) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    /** @var \Drupal\node\Entity\Node $node */
    $node = $node_storage->load($paragraph->get('field_node')[0]->target_id);
    $description = '';
    if(!empty($node) && $node->hasField('field_description')) {
      $description = $node->get('field_description') ?? render($node->get('field_description')->value);
    }
    if(!empty($node) && $node->getTitle()) {
      $variables['title'] = $node->getTitle();
    }
    $variables['description'] = $description;
    if(!empty($node) && $node->id()) {
      $variables['url'] = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id());
    }
  }
}

/**
 * Implements template_preprocess_paragraph() for `resource entity` paragraphs.
 */
function sfgovpl_preprocess_paragraph__resource_entity(&$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];
  if ($paragraph->hasField('field_resource') && NULL !== $paragraph->get('field_resource')[0]) {
    $resource_storage = \Drupal::entityTypeManager()->getStorage('resource');
    /** @var \Drupal\eck\Entity\EckEntity $resource */
    $resource = $resource_storage->load($paragraph->get('field_resource')[0]->target_id);
    $uri = $resource->get('field_url')->uri ?? '';
    $variables['title'] = $resource->get('title')->value;
    $variables['description'] = $resource->get('field_description') ?? render($resource->get('field_description')->value);
    $variables['url'] = $uri;
  }
}

/*
 * Implements hook_preprocess_paragraph().
 */
function sfgovpl_preprocess_paragraph__form_io(&$variables) {
  /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
  $paragraph = $variables['paragraph'];
  $parent_node = $paragraph->getParentEntity();
  if ($parent_node instanceof NodeInterface) {
    $json_data = $parent_node->get('field_formio_json_content')->value;
    $variables['content']['formio_json_data'] = $json_data;
  }
}
