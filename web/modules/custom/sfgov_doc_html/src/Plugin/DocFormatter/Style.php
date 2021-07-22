<?php

namespace Drupal\sfgov_doc_html\Plugin\DocFormatter;

use DOMDocument;
use Drupal\sfgov_doc_html\Plugin\DocFormatterBase;

/**
 * Provides a plugin for stripping inline styles.
 *
 * @DocFormatter(
 *  id = "style",
 *  label = "Style",
 *  weight = 20,
 *  description = "Strips inline styles."
 * )
 */
class Style extends DocFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function format(DOMDocument &$document) {
    $html = $document->saveHTML();

    // Replace all inline style tags.
    $clean = preg_replace('#(<[a-z ]*)(style=("|\')(.*?)("|\'))([a-z ]*>)#', '\\1\\6', $html);

    $document->loadHTML($clean);
  }

}
