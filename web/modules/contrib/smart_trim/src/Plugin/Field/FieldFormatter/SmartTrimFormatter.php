<?php

namespace Drupal\smart_trim\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Component\Utility\Unicode;
use Drupal\smart_trim\Truncate\TruncateHTML;

/**
 * Plugin implementation of the 'smart_trim' formatter.
 *
 * @FieldFormatter(
 *   id = "smart_trim",
 *   label = @Translation("Smart trimmed"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string",
 *     "string_long"
 *   },
 *   settings = {
 *     "trim_length" = "300",
 *     "trim_type" = "chars",
 *     "trim_suffix" = "...",
 *     "more_link" = FALSE,
 *     "more_text" = "Read more",
 *     "summary_handler" = "full",
 *     "trim_options" = ""
 *   }
 * )
 */
class SmartTrimFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'trim_length' => '600',
      'trim_type' => 'chars',
      'trim_suffix' => '',
      'wrap_output' => 0,
      'wrap_class' => 'trimmed',
      'more_link' => 0,
      'more_class' => 'more-link',
      'more_text' => 'More',
      'summary_handler' => 'full',
      'trim_options' => array(),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['trim_length'] = array(
      '#title' => $this->t('Trim length'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 0,
      '#required' => TRUE,
    );

    $element['trim_type'] = array(
      '#title' => $this->t('Trim units'),
      '#type' => 'select',
      '#options' => array(
        'chars' => $this->t("Characters"),
        'words' => $this->t("Words"),
      ),
      '#default_value' => $this->getSetting('trim_type'),
    );

    $element['trim_suffix'] = array(
      '#title' => $this->t('Suffix'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->getSetting('trim_suffix'),
    );

    $element['wrap_output'] = array(
      '#title' => $this->t('Wrap trimmed content?'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('wrap_output'),
      '#description' => $this->t('Adds a wrapper div to trimmed content.'),
    );

    $element['wrap_class'] = array(
      '#title' => $this->t('Wrapped content class.'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('wrap_class'),
      '#description' => $this->t('If wrapping, define the class name here.'),
      '#states' => array(
        'visible' => array(
          ':input[name="fields[body][settings_edit_form][settings][wrap_output]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $element['more_link'] = array(
      '#title' => $this->t('Display more link?'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('more_link'),
      '#description' => $this->t('Displays a link to the entity (if one exists)'),
    );

    $element['more_text'] = array(
      '#title' => $this->t('More link text'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('more_text'),
      '#description' => $this->t('If displaying more link, enter the text for the link.'),
      '#states' => array(
        'visible' => array(
          ':input[name="fields[body][settings_edit_form][settings][more_link]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $element['more_class'] = array(
      '#title' => $this->t('More link class'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('more_class'),
      '#description' => $this->t('If displaying more link, add a custom class for formatting.'),
      '#states' => array(
        'visible' => array(
          ':input[name="fields[body][settings_edit_form][settings][more_link]"]' => array('checked' => TRUE),
        ),
      ),
    );

    if ($this->fieldDefinition->getType() == 'text_with_summary') {
      $element['summary_handler'] = array(
        '#title' => $this->t('Summary'),
        '#type' => 'select',
        '#options' => array(
          'full' => $this->t("Use summary if present, and do not trim"),
          'trim' => $this->t("Use summary if present, honor trim settings"),
          'ignore' => $this->t("Do not use summary"),
        ),
        '#default_value' => $this->getSetting('summary_handler'),
      );
    }

    $trim_options_value = $this->getSetting('trim_options');
    $element['trim_options'] = array(
      '#title' => $this->t('Additional options'),
      '#type' => 'checkboxes',
      '#options' => array(
        'text' => $this->t('Strip HTML'),
        'trim_zero' => t('Honor a zero trim length'),
      ),
      '#default_value' => empty($trim_options_value) ? array() : $trim_options_value,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $unicode = new Unicode();
    $summary = array();
    $type = $this->t('words');
    if ($this->getSetting('trim_type') == 'chars') {
      $type = $this->t('characters');
    }
    $trim_string = $this->getSetting('trim_length') . ' ' . $type;

    if ($unicode->strlen((trim($this->getSetting('trim_suffix'))))) {
      $trim_string .= " " . $this->t("with suffix");
    }
    if ($this->getSetting('more_link')) {
      $trim_string .= ", " . $this->t("with more link");
    }
    $summary[] = $trim_string;

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {

    $element = array();
    $setting_trim_options = $this->getSetting('trim_options');
    $settings_summary_handler = $this->getSetting('summary_handler');
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      if ($settings_summary_handler != 'ignore' && !empty($item->summary)) {
        $output = $item->summary_processed;
      }
      elseif($item->processed != NULL) {
        $output = $item->processed;
      }
      else {
        $output = $item->value;
      }

      // Process additional options (currently only HTML on/off).
      if (!empty($setting_trim_options)) {
        // Allow a zero length trim
        if (!empty($setting_trim_options['trim_zero']) && $this->getSetting('trim_length') == 0) {
          // If the summary is empty, trim to zero length.
          if (empty($item->summary)) {
            $output = '';
          }
          else if ($settings_summary_handler != 'full') {
            $output = '';
          }
        }

        if (!empty($setting_trim_options['text'])) {
          // Strip tags.
          $output = strip_tags(str_replace('<', ' <', $output));

          // Strip out line breaks.
          $output = preg_replace('/\n|\r|\t/m', ' ', $output);

          // Strip out non-breaking spaces.
          $output = str_replace('&nbsp;', ' ', $output);
          $output = str_replace("\xc2\xa0", ' ', $output);

          // Strip out extra spaces.
          $output = trim(preg_replace('/\s\s+/', ' ', $output));
        }
      }

      // Make the trim, provided we're not showing a full summary.
      if ($this->getSetting('summary_handler') != 'full' || empty($item->summary)) {
        $truncate = new TruncateHTML();
        $length = $this->getSetting('trim_length');
        $ellipse = $this->getSetting('trim_suffix');
        if ($this->getSetting('trim_type') == 'words') {
          $output = $truncate->truncateWords($output, $length, $ellipse);
        }
        else {
          $output = $truncate->truncateChars($output, $length, $ellipse);
        }
      }

      // Wrap content in container div.
      if ($this->getSetting('wrap_output')) {
        $output = '<div class="' . $this->getSetting('wrap_class') . '">' . $output . '</div>';
      }

      // Add the link, if there is one!
      $link = '';
      // The entity must have an id already. Content entities usually get their
      // IDs by saving them. In some cases, eg: Inline Entity Form preview there
      // is no ID until everything is saved.
      // https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Entity!Entity.php/function/Entity%3A%3AtoUrl/8.2.x
      if ($entity->id()) {
        $uri = $entity->hasLinkTemplate('canonical') ? $entity->toUrl() : NULL;

        // But wait! Don't add a more link if the field ends in <!--break-->.
        if ($uri && $this->getSetting('more_link') && strpos(strrev($output), strrev('<!--break-->')) !== 0) {
          $more = $this->t($this->getSetting('more_text'));
          $class = $this->getSetting('more_class');

          $project_link = Link::fromTextAndUrl($more, $uri);
          $project_link = $project_link->toRenderable();
          $project_link['#attributes'] = array(
            'class' => array(
              $class,
            ),
          );
          $project_link['#prefix'] = '<div class="' . $class . '">';
          $project_link['#suffix'] = '</div>';
          $link = render($project_link);
        }
      }
      $output .= $link;
      $element[$delta] = array('#markup' => $output);
    }
    return $element;
  }

}
