<?php

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\sfgov_utilities\Utility;

try {
  $topicNodes = Utility::getNodes('topic');

  $topics = [];

  foreach($topicNodes as $topic) {
    $topicItem = [
      "nid" => $topic->id(),
      "title" => $topic->getTitle(),
    ];
    
    $contentTopItems = $topic->get('field_content_top')->getValue();
    $contentItems = $topic->get('field_content')->getValue();

    if (!empty($contentTopItems) || !empty($contentItems)) {
      $topicItem["content_top"] = [];
      $topicItem["content"] = [];

      foreach($contentTopItems as $contentTopItem) {
        $contentTopItemParagraph = Paragraph::load($contentTopItem['target_id']);
        if ($contentTopItemParagraph->getType() === "section") {
          $contentTopItemParagraphItems = $contentTopItemParagraph->get('field_content')->getValue();
          if (!empty($contentTopItemParagraphItems)) {
            foreach ($contentTopItemParagraphItems as $contentTopItemParagraphItem) {
              // $topicItem["content_top"][] = [
              //   "type" => Paragraph::load($contentTopItemParagraphItem['target_id'])->getType()
              // ];
              $topicItem["content_top"][] = Paragraph::load($contentTopItemParagraphItem['target_id'])->getType();
            }
          }
        }
      }

      foreach($contentItems as $contentItem) {
        $contentItemParagraph = Paragraph::load($contentItem['target_id']);
        if ($contentItemParagraph->getType() === "section") {
          $contentItemParagraphItems = $contentItemParagraph->get('field_content')->getValue();
          if (!empty($contentItemParagraphItems)) {
            foreach ($contentItemParagraphItems as $contentItemParagraphItem) {
              // $topicItem["content"][] = [
              //   "type" => Paragraph::load($contentItemParagraphItem['target_id'])->getType()
              // ];
              $topicItem["content"][] = Paragraph::load($contentItemParagraphItem['target_id'])->getType();
            }
          }
        }
      }
      $topicItem["content_top"] = implode(",", $topicItem["content_top"]);
      $topicItem["content"] = implode(",", $topicItem["content"]);
      $topicItem["published"] = $topic->isPublished();
      $topicItem["author"] = \Drupal\user\Entity\User::load($topic->getOwnerId())->getDisplayName();
      $topics[] = $topicItem;

    }
  }

  // print_r($topics);
  echo json_encode($topics, JSON_PRETTY_PRINT);
  echo "\n";
} catch (\Exception $e) {
  error_log($e->getMessage());
}