<?php

/**
 * @file
 * Contains \Drupal\toc_api\Toc.
 */

namespace Drupal\toc_api;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;

/**
 * Defines A class that parses the header tags from an HTML document.
 */
class Toc implements TocInterface {

  /**
   * The source content.
   *
   * @var string
   */
  protected $source;

  /**
   * The options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * The default options.
   *
   * @var array
   */
  protected $defaultOptions = [
    'template' => 'responsive',
    'title' => 'Table of Contents',
    'block' => FALSE,
    'header_count' => 2,
    'header_min' => 2,
    'header_max' => 4,
    'header_allowed_tags' => '<em> <b> <del> <i> <mark> <s> <span> <strong> <sup> <sub> <em> <b> <del> <i> <mark> <s> <span> <strong> <sup> <sub>',
    'header_id' => 'title',
    'header_id_prefix' => 'section',
    'top_label' => 'Back to top',
    'top_min' => 2,
    'top_max' => 2,
    'number_path' => TRUE,
    'number_path_separator' => '.',
    'number_path_truncate' => TRUE,
    'default' => [
      'number_type' => 'decimal',
      'number_prefix' => '',
      'number_suffix' => ') ',
    ],
    'headers' => [
      'h1' => [],
      'h2' => [],
      'h3' => [],
      'h4' => [],
      'h5' => [],
      'h6' => [],
    ],
  ];

  /**
   * An array of allowed tag names.
   *
   * @var array
   */
  protected $allowedTags;

  /**
   * The number of root headers found in the content.
   *
   * @var int
   */
  protected $headerCount;

  /**
   * The source content with unique header ids.
   *
   * @var string
   */
  protected $content;

  /**
   * The index headers.
   *
   * @var array
   */
  protected $index = [];

  /**
   * The headers keyed by id.
   *
   * @var array
   */
  protected $ids = [];

  /**
   * The toc represent as a tree.
   *
   * @var array
   */
  protected $tree;

  /**
   * Constructs a new TOC object.
   *
   * @param string $source
   *   The HTML content that contains header tags used to create a table of
   *   contents.
   * @param array $options
   *   (optional) An associative array of options used to generate a table of
   *   contents and bookmarked headers.
   *   elements:
   *
   *   - 'template': Template for table of contents.
   *     Possible values: 'tree', 'menu', 'responsive'
   *     Default value is responsive.
   *   - 'title': (optional) Title for table of contents.
   *     Default value is 'Table of contents'.
   *   - 'block': (optional) If TRUE table of contents will be displayed in a
   *     block.
   *
   *   - 'header_count': The minimum number of top level headers required to
   *     create a table of contents.
   *     Default value is 2
   *   - 'header_min': The minimum level of header to be included.
   *     Default value is 4
   *   - 'header_max': The maximum level of header to be included.
   *     Default value is 2
   *   - 'header_allowed_tags': List of HTML tags allowed inside a header.
   *   - 'header_id': Type of header id.
   *      Possible values 'title', 'path', 'key'.
   *   - 'header_id_prefix': Prefix to be prepended to header id when
   *     'path' or 'key' is selected.
   *     Default value is 'section'
   *
   *   - 'top_min': The minimum level of header to include a back to top link.
   *     Default value is 2
   *   - 'top_max': The maximum level of header to include a back to top link.
   *     Default value is 2
   *   - 'top_label': The text to be displayed in back to top links.
   *     Default value is 'Back to top'
   *
   *   - 'number_path': Display the full path inside the header tag.
   *      Default value is TRUE.
   *   - 'number_path_separator': (optional) Path separator used to display the
   *      full hierarchy in a header.
   *   - 'number_path_truncate': (optional) If TRUE empty value (ie 0) will be
   *      removed from path.
   *     - 'default.top': Displays back to top link. If FALSE back to link is
   *      hidden.
   *
   *   - 'default': A associative array containing options for header indexes.
   *     - 'default.number_type': List style type.
   *       Possible values: 'decimal', 'upper-alpha', 'lower-alpha',
   *       'upper-roman', 'lower-roman', 'disc', 'circle', 'square', or 'none'
   *       Defaults to 'decimal',
   *     - 'default.number_prefix': Text to added before the header type.
   *       Defaults to ''
   *     - 'default.number_suffix': Text to added after the header type.
   *       Defaults to ') '
   *
   *   - 'h1 - h2': Header specific settings that override the 'default'
   *     settings.
   */
  public function __construct($source, $options = []) {
    $this->source = $source;

    // Set default options for each header tag.
    $this->options = NestedArray::mergeDeep($this->defaultOptions, $options);
    for ($i = 1; $i <= 6; $i++) {
      $tag = 'h' . $i;
      if ($i >= $this->options['header_min'] && $i <= $this->options['header_max']) {
        $this->options['headers'][$tag] = NestedArray::mergeDeep($this->options['default'], $this->options['headers'][$tag]);
      }
      else {
        unset($this->options['headers'][$tag]);
      }
    }
    $this->allowedTags = $this->formatter()->convertAllowedTagsToArray($this->options['header_allowed_tags']);

    $this->initialize();

    // DEBUG:
    // dsm($this->getIndex());
    // dsm($this->getTree());
  }

  /**
   * Initializes the table of content index and ensure unique header ids.
   */
  protected function initialize() {
    $this->index = [];

    // Setup an empty array of keys to track the index's keys.
    $default_keys = [];
    foreach (array_keys($this->options['headers']) as $tag) {
      $default_keys[$tag] = 0;
    }

    $index_keys = $default_keys;

    $dom = Html::load($this->source);
    // Loop through all the tags to ensure headers are found in the correct
    // order.
    $dom_nodes = $dom->getElementsByTagName('*');
    /** @var \DOMElement $dom_node */
    foreach ($dom_nodes as $dom_node) {
      if (empty($this->options['headers'][$dom_node->tagName])) {
        continue;
      }

      // Set header tag and options.
      $header_tag = $dom_node->tagName;
      $header_options = $this->options['headers'][$header_tag];

      // Set header html and title.
      $header_html = '';
      foreach ($dom_node->childNodes as $child_node) {
        $header_html .= $dom_node->ownerDocument->saveHTML($child_node);
      }
      $header_title = strip_tags($header_html);

      // Set header key, number, and parent.
      $header_number = NULL;
      $header_key = NULL;
      $header_path = NULL;
      $parent_key = NULL;
      $header_keys = $default_keys;
      $header_level = (int) $dom_node->tagName[1];
      for ($level = $this->options['header_min']; $level <= $this->options['header_max']; $level++) {
        $tag = "h$level";
        if ($level == $header_level) {
          // When header level is matched, increment the index key and set the
          // header number.
          $header_number = ++$index_keys[$tag];
        }
        elseif ($level > $header_level) {
          // Reset index keys once a header level is met.
          $index_keys[$tag] = 0;
        }
        $header_keys[$tag] = $index_keys[$tag];

        // Now set the parent key for every header level to ensure this header
        // has a parent.
        if ($level < $header_level) {
          $parent_key = implode('.', $header_keys);
          if (!isset($this->index[$parent_key])) {
            $parent_key = NULL;
          }
        }
      }
      $header_key = implode('.', $header_keys);

      // Set header parts and path from converted keys.
      $header_path = implode($this->options['number_path_separator'], $this->formatter()->convertHeaderKeysToValues($header_keys, $this->options));

      // Append to this header to it's parent.
      if ($parent_key) {
        $this->index[$parent_key]['children'][$header_key] = $header_key;
      }

      // Set header value based on (list) type.
      $header_value = $this->formatter()->convertNumberToListTypeValue($header_number, $header_options['number_type']);

      // Get and reset (unique) header id attribute.
      if ($dom_node->getAttribute('id')) {
        $header_id = $dom_node->getAttribute('id');
      }
      else {
        $id_type = $this->options['header_id'];
        $id_prefix = $this->options['header_id_prefix'] ?: 'section';
        switch ($id_type) {
          case 'title':
            $header_id = $this->formatter()->convertStringToId($header_title);
            break;

          case 'number_path':
            $header_id = $id_prefix . '-' . $header_path;
            break;

          case 'key':
          default:
            $header_id = $id_prefix . '-' . $header_key;
            break;
        }
      }
      $header_id = $this->uniqueId($header_id);
      $dom_node->setAttribute('id', $header_id);

      // Track the header's id and map it to the header's key.
      // This is used to lookup the parent and children relationships.
      $this->ids[$header_id] = $header_key;

      // Set header in index.
      $this->index[$header_key] = [
        'type' => $header_options['number_type'],
        'tag' => $header_tag,
        'level' => $header_level,
        'key' => $header_key,
        'keys' => $header_keys,
        'indent' => ($header_level - $this->options['header_min']),
        'path' => $header_path,
        'number' => $header_number,
        'value' => $header_value,
        'parent' => $parent_key,
        'children' => [],
        'id' => $header_id,
        'title' => $header_title,
        'html' => [
          '#markup' => $header_html,
          '#allowed_tags' => $this->getAllowedTags(),
        ],
        'url' => Url::fromRoute('<none>', NULL, [
            'fragment' => $header_id,
          ]
        ),
      ];
    }
    $this->content = Html::serialize($dom);
  }

  /**
   * Gets the TOC formatter.
   *
   * @return \Drupal\toc_api\TocFormatter.
   *   The TOC formatter
   */
  protected function formatter() {
    return \Drupal::service('toc_api.formatter');
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->options['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedTags() {
    return $this->allowedTags;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaderCount() {
    if (!isset($this->headerCount)) {
      foreach ($this->index as $item) {
        if (empty($item['parent'])) {
          $this->headerCount++;
        }
      }
    }
    return $this->headerCount;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    if (!isset($this->tree)) {
      $this->tree = [
        'title' => $this->options['title'],
      ];

      // Collect all the header that do not have parents.
      $children = [];
      foreach ($this->index as $key => $item) {
        if (empty($item['parent'])) {
          $children[] = $key;
        }
      }

      $this->buildTree($this->tree, $children);
    }
    return $this->tree;
  }

  /**
   * Recursively builds a hierarchical array of headers.
   *
   * @param array &$item
   *   A associative array for a parent header item.
   * @param array $children
   *   An array of keys to be associative to the parent header item.
   */
  protected function buildTree(array &$item, array $children) {
    $item['below_type'] = '';
    $item['below'] = [];
    foreach ($children as $key) {
      $child_item = $this->index[$key];
      $this->buildTree($child_item, $child_item['children']);
      $item['below_type'] = $child_item['type'];
      $item['below'][$key] = $child_item;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isBlock() {
    return $this->options['block'];
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    if ($this->getHeaderCount() < $this->options['header_count']) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Generate a unique header id.
   *
   * @param string $id
   *   A header id.
   *
   * @return string
   *   A unique header id, possibly suffixed with numeric increment.
   */
  protected function uniqueId($id) {
    $unique_id = $id;
    $i = 1;
    while (isset($this->ids[$id])) {
      $unique_suffix = '-' . sprintf("%02s", $i);
      $id = $unique_id . $unique_suffix;
      $i++;
    }
    return $id;
  }

}
