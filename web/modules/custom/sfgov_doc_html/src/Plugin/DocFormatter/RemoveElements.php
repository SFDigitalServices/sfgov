<?php

namespace Drupal\sfgov_doc_html\Plugin\DocFormatter;

use DOMDocument;
use DOMXPath;
use Drupal\sfgov_doc_html\Plugin\DocFormatterBase;

/**
 * Provides a plugin for removing unwanted elements.
 *
 * @DocFormatter(
 *  id = "remove_elements",
 *  label = "Remove Elements",
 *  weight = 30,
 *  description = "Removes unwanted elements such as title and style tags."
 * )
 */
class RemoveElements extends DocFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function format(DOMDocument &$document) {
    $tags_to_remove = [
      'title',
      'style',
      'meta',
    ];

    foreach ($tags_to_remove as $tag_name) {
      $nodes = $document->getElementsByTagName($tag_name);
      for ($i = $nodes->length - 1; $i >= 0; $i--) {
        $nodes->item($i)->parentNode->removeChild($nodes->item($i));
      }
    }

    // Remove comments.
    $xpath = new DOMXPath($document);
    foreach ($xpath->query('//comment()') as $comment) {
      $comment->parentNode->removeChild($comment);
    }
  }

}
