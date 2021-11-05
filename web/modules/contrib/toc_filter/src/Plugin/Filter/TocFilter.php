<?php

/**
 * @file
 * Contains \Drupal\toc_filter\Plugin\Filter\TocFilter.
 */

namespace Drupal\toc_filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\toc_api\Entity\TocType;

/**
 * Provides a filter to display a table of contents.
 *
 * IMPORTANT:
 * TOC options is an associative array which is not easily supported via filter
 * settings because every single options must be defined with default values.
 * This limitation would require all TOC options to duplicated in the below
 * settings annotation.
 *
 * So the eases solution was to serialize the options before they are stored
 * as configuration.
 *
 * @see: \Drupal\toc_filter\Plugin\Filter\TocFilter::settingsForm
 * @see: toc_filter_form_filter_format_edit_form_submit
 *
 * @Filter(
 *   id = "toc_filter",
 *   module = "toc_filter",
 *   title = @Translation("Display a table of contents"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "type" = "default",
 *     "auto" = FALSE,
 *     "block" = FALSE,
 *   },
 * )
 */
class TocFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $toc_types = TocType::loadMultiple();
    foreach ($toc_types as $toc_type) {
      $types[$toc_type->id()] = $toc_type->label();
    }

    $form['type'] = [
      '#title' => $this->t('Type'),
      '#type' => 'select',
      '#options' => $types,
      '#default_value' => $this->settings['type'] ?: 'default',
    ];
    $form['auto'] = [
      '#title' => $this->t('Automatically include table of contents'),
      '#description' => $this->t('If set, a table of contents will be added when a <code>[toc]</code> token is not present in the content.'),
      '#type' => 'select',
      '#options' => [
        '' => '',
        'top' => $this->t('At the top of the page'),
        'bottom' => $this->t('At the bottom of the page'),
      ],
      '#default_value' => $this->settings['auto'],
    ];
    $form['block'] = [
      '#title' => $this->t('Display table of contents in a block.'),
      '#description' => $this->t('Please make sure to place the <a href=":href">TOC filter</a> block on your site.', [':href' => URL::fromRoute('block.admin_display')->toString()]),
      '#type' => 'checkbox',
      '#default_value' => $this->settings['block'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    // If no [toc] is found, see if [toc] can be automatically added, else
    // return.
    if (stripos($text, '[toc') === FALSE) {
      switch ($this->settings['auto']) {
        case 'top':
          $text = '[toc]' . $text;
          break;

        case 'bottom':
          $text .= '[toc]';
          break;

        case '':
        default:
          return $result;

      }
    }

    // Remove block tags around token.
    $text = preg_replace('#<(p|div|h\d|blockquote)[^>]*>\s*(\[toc[^]]*\])\s*</\1>#', '\2', $text);

    // Get custom options.
    if ($this->settings['type'] && ($toc_type = TocType::load($this->settings['type']))) {
      $toc_type_options = $toc_type->getOptions() ?: [];
      $result->addCacheableDependency($toc_type);
    }
    else {
      $toc_type_options = [];
    }

    // Replace first [toc] token and update the content.
    if (!preg_match('#\[toc([^]]*)?\]#is', $text, $match)) {
      return $result;
    }

    // Remove the [toc] token for the processed text.
    // This makes it easier to just return the original text.
    $result->setProcessedText(str_replace($match[0], '', $text));

    // Parse inline options (aka attributes).
    $inline_options = self::parseOptions($match[1]);

    // Add custom setting to inline options.
    $inline_options += ['block' => $this->settings['block']];

    // Merge with default, global, filter, and inline options.
    $options = NestedArray::mergeDeepArray([
      $toc_type_options,
      $inline_options,
    ]);

    // Allow TOC filter options to be altered and optionally set to FALSE,
    // which will block a table of contents from being added.
    \Drupal::moduleHandler()->alter('toc_filter', $text, $options);

    // If $option is FALSE, then just return the unprocessed result w/o
    // the [toc] token.
    if ($options === FALSE) {
      return $result;
    }

    /** @var \Drupal\toc_api\TocManagerInterface $toc_manager */
    $toc_manager = \Drupal::service('toc_api.manager');
    /** @var \Drupal\toc_api\TocBuilderInterface $toc_builder */
    $toc_builder = \Drupal::service('toc_api.builder');
    /** @var \Drupal\toc_api\TocInterface $toc */
    $toc = $toc_manager->create('toc_filter', $text, $options);

    // If table of content is not visible, return the unprocessed result w/o
    // the [toc] token.
    if (!$toc->isVisible()) {
      return $result;
    }

    // Replace the text with the render the content.
    $text = '<div class="toc-filter">' . $toc_builder->renderContent($toc) . '</div>';

    // If block remove [toc] token, else replace it with the rendered TOC.
    if ($toc->isBlock()) {
      $text = str_replace($match[0], '', $text);
    }
    else {
      $text = str_replace($match[0], $toc_builder->renderToc($toc), $text);
    }

    return $result->setProcessedText($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t("Converts header tags into a hierarchical table of contents. (i.e [toc type=(tree|menu|responsive) title='Table of Contents']");
  }

  /**
   * Parse options from an attributes string.
   *
   * @param string $text
   *   A string of options.
   *
   * @return array
   *   An associative array of parsed name/value pairs.
   */
  static public function parseOptions($text) {
    // Decode special characters.
    $text = html_entity_decode($text);

    // Convert decode &nbsp; to expected ASCII code 32 character.
    // See: http://stackoverflow.com/questions/6275380
    $text = str_replace("\xA0", ' ', $text);

    // Create a DomElement so that we can parse its attributes as options.
    $html = Html::load('<div ' . $text . ' />');
    $dom_node = $html->getElementsByTagName('div')->item(0);

    $options = [];
    foreach ($dom_node->attributes as $name => $node) {
      // Empty attribute values (ie name="") and attributes with no defined
      // value (ie just name) both return empty strings.
      // See: http://stackoverflow.com/questions/6232412
      // So we are going to work-around this limitation and look at the
      // actually attribute to see if is assigned a value, if not then set it to
      // 'true'.
      $value = $node->nodeValue ?: (preg_match('/' . preg_quote($name) . '\s*=/i', $text) ? '' : 'true');

      switch (strtolower($value)) {
        case 'true':
        case 'false':
          $value = (strtolower($value) === 'true');
          break;

        default:
          if ($value !== '' && is_numeric($value)) {
            $value = floatval($value);
          }
          break;
      }

      // Prefix h1-6 tags with 'headers.' to map it to the correction
      // $option['headers'] array.
      if (preg_match('/h[1-6]/', $name)) {
        $name = 'headers.' . $name;
      }

      self::setOption($options, $name, $value);
    }
    return $options;
  }

  /**
   * Set nested option name and value.
   *
   * From: http://stackoverflow.com/questions/9635968
   *
   * @param array $options
   *   An associative array of options.
   * @param string $name
   *   Option name with path delimited by period.
   * @param mixed $value
   *   Option value.
   */
  static protected function setOption(array &$options, $name, $value) {
    $keys = explode('.', $name);
    while ($key = array_shift($keys)) {
      $options =& $options[$key];
    }
    $options = $value;
  }

}
