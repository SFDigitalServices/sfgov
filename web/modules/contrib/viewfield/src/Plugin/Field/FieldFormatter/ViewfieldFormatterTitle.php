<?php

namespace Drupal\viewfield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\views\Views;

/**
 * Plugin implementation of the 'viewfield_title' formatter.
 *
 * @FieldFormatter(
 *   id = "viewfield_title",
 *   label = @Translation("Title and display name"),
 *   field_types = {"viewfield"}
 * )
 */
class ViewfieldFormatterTitle extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $value = $item->getValue();
      $target_id = $value['target_id'];
      $display_id = $value['display_id'];
      $arguments = $value['arguments'];
      $view = Views::getView($target_id);
      $title = $view->getTitle();
      $display = $view->displayHandlers->get($display_id);
      if (!$display) {
        $elements[$delta] = [
          '#theme' => 'item_list',
          '#items' => [
            $this->t('Missing or broken view/display'),
          ],
        ];
        continue;
      }
      $display_name = $display->pluginTitle();
      $elements[$delta] = [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('<strong>View:</strong> @title (@id)', [
            '@title' => $title,
            '@id' => $target_id
          ]),
          $this->t('<strong>Display:</strong> @display (@id)', [
            '@display' => $display_name,
            '@id' => $display_id
          ]),
          $this->t('<strong>Arguments:</strong> @arguments', ['@arguments' => $arguments]),
        ],
      ];
    }
    return $elements;
  }
}

