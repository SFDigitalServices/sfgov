<?php

use \Drupal\media\entity\Media;

/**
 * Create media entities for existing profile field_photo_images and assign to new field_profile_photo media entity reference
 */
function sfgov_utilities_deploy_profile_photos() {
  $nids = \Drupal::entityQuery('node')->condition('type','person')->execute();
  $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

  $mediaIds = \Drupal::entityQuery('media')->condition('bundle', 'image')->execute();
  $mediaImages = Media::loadMultiple($mediaIds);
  $mediaFileNames = [];

  foreach($mediaImages as $mediaImage) {
    $mediaFileNames[] = trim($mediaImage->getName());
  }

  foreach($nodes as $node) {
    $title = $node->getTitle();
    $field_photo_entity = $node->field_photo->entity;

    if (!empty($field_photo_entity)) {
      $field_photo_uri = $field_photo_entity->getFileUri();
      $field_photo_filename = trim(substr($field_photo_uri, strrpos($field_photo_uri, "/")+1));
      $field_photo_id = $field_photo_entity->id();
  
      echo $title . " (" . $field_photo_id . "):" . "[" . $field_photo_filename . "] ";
  
      if (array_search($field_photo_filename, $mediaFileNames) == false) {
        echo "...no media found, create and assign";
        $media_image = Media::create([
          'bundle' => 'image',
          'uid' => \Drupal::currentUser()->id(),
          'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
          'field_image' => [
            'target_id' => $field_photo_id,
            'alt' => t($title),
            'title' => t($title),
          ],
          'field_media_image' => [
            "target_id" => $field_photo_id,
            'alt' => t($title),
            'title' => t($title),
          ]
        ]);
        $media_image->setPublished(true);
        $media_image->save();
  
        $node->field_profile_photo->target_id = $media_image->id();
        $node->save();
  
      } else {
        echo "...media found, skip";
      }
  
      echo "\n";
    }
  }
}
