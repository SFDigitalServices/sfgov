<?php

namespace Drupal\sfgov_translation\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides Translation Status (Lionbridge) field handler.
 *
 * @ViewsField("lionbridge_translation_status")
 *
 */
class LionbridgeTranslationStatus extends FieldPluginBase {

    /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->currentDisplay = $view->current_display;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['has_translation'] = ['default' => 'en'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $langcodes = array_keys(\Drupal::languageManager()->getLanguages());
    $options = [];

    foreach ($langcodes as $langcode) {
      $options[$langcode] = $langcode;
    }

    $form['has_translation'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => $options,
      '#default_value' => $this->options['has_translation'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $values->_entity;
    $language_id = $this->options['has_translation'];
    $nid = $node->id();

    // Translation data.
    $translation_exists = $node->hasTranslation($language_id);
    if ($translation_exists) {
      $translated_node = $node->getTranslation($language_id);
      $translation_outdated = $translated_node->translation_outdated->value;
      $translated_node_link = $translated_node->toUrl('edit-form')->toString();
    }

    // Job data.
    $current_jobs = tmgmt_job_item_load_latest('content', 'node', $nid, 'en');
    $job_status = isset($current_jobs[$language_id]) ? $current_jobs[$language_id]->state->value : NULL;

    // Use the translation and job data to set the correct icon path.
    // No translation, and no translations in progress.
    if (!$translation_exists && !$job_status) {
      $icon_path = '/core/misc/icons/bebebe/ex.svg';
    }
    // Job translation in progress.
    elseif ($job_status == 1 || $job_status == 2) {
      $icon_path = '/modules/contrib/tmgmt/icons/hourglass.svg';
    }
    // Translation is marked as outdated.
    elseif ($translation_exists && $translation_outdated) {
      $icon_path = '/modules/contrib/tmgmt/icons/outdated.svg';
    }
    // Translation is complete and not outdated.
    elseif ($translation_exists && !$translation_outdated && ($job_status == 3 || $job_status == NULL)) {
      $icon_path = '/core/misc/icons/73b355/check.svg';
    }

    // Assemble the icon with a link if possible.
    $icon_image = '<img width="16" height="16" src="' . $icon_path . '">';
    if (isset($translated_node_link)) {
      $icon_image = '<a href=' . $translated_node_link . '>' . $icon_image . '</a>';
    }
    $icon = '<div class="tmgmt-legend-icon">' . $icon_image . '</div>';

    // Return an uncached icon so that it updates properly.
    return [
      '#type' => 'markup',
      '#markup' => t($icon),
      '#cache' => [
        'max-age' => 0,
      ]
    ];
  }

}
