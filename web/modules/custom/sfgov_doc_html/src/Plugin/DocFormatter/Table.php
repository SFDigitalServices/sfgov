<?php

namespace Drupal\sfgov_doc_html\Plugin\DocFormatter;

use DOMDocument;
use Drupal\sfgov_doc_html\Plugin\DocFormatterBase;

/**
 * Provides a plugin for entity reference fields.
 *
 * @DocFormatter(
 *  id = "table",
 *  label = "Table",
 *  description = "Formats table."
 * )
 */
class Table extends DocFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function format(DOMDocument &$document) {
    /** @var \DOMElement $table */
    foreach ($document->getElementsByTagName("table") as $table) {
      $rows = $table->getElementsByTagName('tr');
      $last_row = $rows[count($rows) - 1];
      $last_row_value = $last_row->nodeValue;

      // Remove row if whole row is empty.
      foreach ($rows as $row) {
        $row_value = $row->nodeValue;
        if ($last_row_value === $row_value && trim(str_replace(array("\r", "\n", "&nbsp;"), '', htmlentities($row_value))) === "") {
          $row->parentNode->removeChild($row);
        }
      }

      // Add table caption.
      if ($caption = $this->findTableCaption($table)) {
        $table_caption = $document->createElement('caption');
        $table_caption->nodeValue = $caption->nodeValue;
        $table->appendChild($table_caption);

        $caption->parentNode->removeChild($caption);
      }
    }


    foreach ($document->getElementsByTagName("table") as $table) {
      $table->setAttribute('class', 'sfgov-table');
      /** @var \DOMElement $row */
      foreach ($table->childNodes as $row) {
        /** @var \DOMElement $cell */
        // Remove all non tr children.
        if ($row->nodeName !== "tr") {
          $table->removeChild($row);
        }

        // Remove empty cells.
        foreach ($row->childNodes as $cell) {
          if ($cell->nodeName !== "td" || $cell->textContent === "\n") {
            $row->removeChild($cell);
          }
        }
      }
    }

    foreach ($document->getElementsByTagName("table") as $table) {
      $table->setAttribute('class', 'sfgov-table');
      $table->removeAttribute('style');
      $rows = $table->getElementsByTagName('tr');

      foreach ($rows as $row) {
        $cells = $row->getElementsByTagName('td');
        foreach ($cells as $cell_position => $cell) {
          $cell->nodeValue = trim(str_replace(array("\r", "\n"), '', $cell->nodeValue));

          // Remove empty wrapper cells added by phpword.
          if (htmlentities($cell->nodeValue) === "&nbsp;" && ($cell_position === 0 || $cell_position === count($cells) - 1)) {
            $row->removeChild($cell);
          }

          if (htmlentities($cell->nodeValue) === "&nbsp;") {
            $cell->nodeValue = "";
          }
        }
      }

      // Remove styles from span wrappers.
      foreach ($table->getElementsByTagName('span') as $span) {
        $span->removeAttribute('style');
      }

      /** @var \DOMElement $p */
      foreach ($table->getElementsByTagName('p') as $p) {
        // Remove empty paragraphs.
        if (htmlentities($p->nodeValue) === "&nbsp;") {
          $p->parentNode->removeChild($p);
        }
      }

      /** @var \DOMElement $td */
      foreach ($table->getElementsByTagName('td') as $td) {
        $td_value = htmlentities(trim(str_replace(array("\r", "\n"), '', $td->nodeValue)));

        // phpword converts all cells to td.
        // Since there's no way to really determine which ones are th,
        // We assume the ones with a bgcolor value are th.
        if ($td->getAttribute('bgcolor') !== '') {
          $td->parentNode->setAttribute('class', 'header-row');
          $td->parentNode->setAttribute('data-is-header', TRUE);
        }

        if ($td_value === "&nbsp;" || $td_value === "") {
          $td->nodeValue = NULL;
        }
        else {
          foreach ($td->childNodes as $child) {
            if ($child->nodeValue === "\n") {
              $td->removeChild($child);
            }
          }

          // Wrap cell content in a div so that styles can be applied to the div
          // without breaking cells formatting.
          $new_div = $document->createElement('div');
          $new_div->nodeValue = $td_value;
          $td->replaceChild($new_div, $td->firstChild);
        }
      }

      /** @var \DOMElement $tr */
      // Remove row if whole row is empty.
      // We do this again after the whole table has been transversed once.
      // This way it's easier to handle heading rows.
      $rows = $table->getElementsByTagName('tr');
      foreach ($rows as $position => $tr) {
        $is_header = $tr->getAttribute('data-is-header');
        if (!$is_header && trim(str_replace(array("\r", "\n", "&nbsp;"), '', htmlentities($tr->nodeValue))) === "") {
          if (!(($next = $rows[$position + 1]) && $next->getAttribute('data-is-header'))) {
            $tr->parentNode->removeChild($tr);
          }
        }
      }
    }
  }

  /**
   * Helper to find the caption for a table.
   *
   * @param \DOMElement $table
   *   The table element.
   *
   * @return \DOMElement|null
   *   The caption element or null if not found.
   */
  protected function findTableCaption(\DOMElement $table) {
    $paragraph = NULL;
    $next = $table;


    while ($next && ($next = $next->nextSibling)) {
      // Find the next non-empty paragraph.
      if ($next instanceof \DOMElement && $next->tagName === "p" && trim(str_replace(array("\r", "\n", "&nbsp;"), '', htmlentities($next->nodeValue))) !== "") {

        // If the paragraph textContent starts with 'Table' and has text-align: center,
        // Assume this is the table caption.
        if (strpos($next->textContent, 'Table') === 0 && strpos($next->getAttribute('style'), 'text-align: center;') !== FALSE) {
          $paragraph = $next;
        }

        // Bail out when see any non-empty paragraph.
        break;
      }
    }
    return $paragraph;
  }

}
