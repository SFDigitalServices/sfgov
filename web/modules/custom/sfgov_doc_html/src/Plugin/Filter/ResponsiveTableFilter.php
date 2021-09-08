<?php

namespace Drupal\sfgov_doc_html\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to handle responsive tables.
 *
 * @Filter(
 *   id = "responsive_table",
 *   module = "sfgov_doc_html",
 *   title = @Translation("Responsive tables"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class ResponsiveTableFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $document = Html::load($text);
    $tables = $document->getElementsByTagName('table');
    /** @var \DOMElement $table */
    foreach ($tables as $index => $table) {
      $table_index = $index + 1;
      $table_id = "table-{$table_index}-caption";

      $className = $table->getAttribute('class');
      if (strpos($className, 'sfgov-table') === FALSE) {
        $table->setAttribute('class', $className . ' sfgov-table sfgov-table-with-padding');
      }

      // Remove the default border and cell attributes.
      $table->removeAttribute('border');
      $table->removeAttribute('cellpadding');
      $table->removeAttribute('cellspacing');

      // Wrap the table.
      if (strpos($table->parentNode->getAttribute('class'), 'sfgov-table') === FALSE) {
        $table_wrapper = $document->createElement('div');
        $table_wrapper->setAttribute('class', 'sfgov-table-wrapper');
        $table_copy = $table->cloneNode(TRUE);
        $table_wrapper->appendChild($table_copy);
        $table->parentNode->replaceChild($table_wrapper, $table);

        // Add table caption before responsive wrapper.
        if ($captions = $table->getElementsByTagName('caption')) {
          $table_caption = $document->createElement('p');
          $table_caption->setAttribute('id', $table_id);
          $table_caption->setAttribute('class', 'sfgov-table-caption');
          $table_caption->nodeValue = $captions->item(0)->nodeValue;
          $table_wrapper->parentNode->insertBefore($table_caption, $table_wrapper);
          $table_copy->setAttribute('aria-labelledby', $table_id);

          // Delete captions.
          foreach ($table_copy->childNodes as $child) {
            if ($child->nodeName === 'caption') {
              $table_copy->removeChild($child);
            }
          }
        }
      }
    }
    return new FilterProcessResult(Html::serialize($document));
  }

}
