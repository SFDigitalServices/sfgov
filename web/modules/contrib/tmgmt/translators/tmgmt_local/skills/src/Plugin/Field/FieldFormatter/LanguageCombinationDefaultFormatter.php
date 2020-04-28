<?php

namespace Drupal\tmgmt_language_combination\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'tmgmt_language_combination_default' formatter.
 *
 * @FieldFormatter(
 *   id = "tmgmt_language_combination_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "tmgmt_language_combination",
 *   }
 * )
 */
class LanguageCombinationDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $installed_languages = \Drupal::languageManager()->getLanguages();
    foreach ($items as $delta => $item) {
      $from = $installed_languages[$item->language_from]->getName();
      $to = $installed_languages[$item->language_to]->getName();
      $elements[$delta]['#markup'] = t('From @from to @to', ['@from' => $from, '@to' => $to]);
    }

    return $elements;
  }

}
