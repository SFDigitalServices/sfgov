<?php

namespace Drupal\sfgov_doc_html\Plugin\DocFormatter;

use DOMDocument;
use Drupal\sfgov_doc_html\Plugin\DocFormatterBase;

/**
 * Provides a plugin for removing empty paragraphs.
 *
 * @DocFormatter(
 *  id = "empty_paragraphs",
 *  label = "Empty Paragraphs",
 *  description = "Removes successive empty paragraphs."
 * )
 */
class EmptyParagraphs extends DocFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function format(DOMDocument &$document) {
    $paragraphs = $document->getElementsByTagName('p');

    for ($i = $paragraphs->length - 1; $i >= 0; $i--) {
      if (htmlentities($paragraphs->item($i)->textContent) == '&nbsp;') {
        $paragraphs->item($i)->parentNode->removeChild($paragraphs->item($i));
      }
    }
  }

}
