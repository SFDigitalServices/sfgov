<?php

namespace Drupal\sfgov_doc_html\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for doc search.
 *
 * @RenderElement("docsearch")
 */
class DocSearch extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'docsearch',
      '#search_target' => NULL,
      '#attached' => [
        'library' => [
          'sfgov_doc_html/docsearch',
        ],
      ],
    ];
  }

}
