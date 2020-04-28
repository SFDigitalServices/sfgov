<?php
/**
 * @file
 * Contains \Drupal\google_analytics_reports\Plugin\views\query\GoogleAnalyticsQuery.
 */

namespace Drupal\google_analytics_reports\Plugin\views\query;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Views query class for Google Analytics Reports API.
 *
 * @ViewsQuery(
 *   id = "google_analytics_query",
 *   title = @Translation("Google Analytics Query"),
 *   help = @Translation("Defines a Views query class for Google Analytics Reports API.")
 * )
 */
class GoogleAnalyticsQuery extends QueryPluginBase {

  /**
   * A list of tables in the order they should be added, keyed by alias.
   */
  protected $tableQueue = [];

  /**
   * An array of fields.
   */
  protected $fields = [];

  /**
   * An array mapping table aliases and field names to field aliases.
   */
  protected $fieldAliases = [];

  /**
   * An array of sections of the WHERE query.
   *
   * Each section is in itself an array of pieces and a flag as to whether
   * or not it should be AND or OR.
   */
  protected $where = [];

  /**
   * A simple array of order by clauses.
   */
  protected $orderby = [];

  /**
   * The default operator to use when connecting the WHERE groups.
   */
  protected $groupOperator = 'AND';

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler;
   */
  public $moduleHandler;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructor; Create the basic query object and fill with default values.
   *
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->unpackOptions($this->options, $options);
  }

  /**
   * Add a metric or dimension to the query.
   *
   * @param string $table
   *   NULL in most cases, we could probably remove this altogether.
   * @param string $field
   *   The name of the metric/dimension/field to add.
   * @param string $alias
   *   Probably could get rid of this too.
   * @param array $params
   *   Probably could get rid of this too.
   *
   * @return string
   *   The name that this field can be referred to as.
   */
  public function addField($table, $field, $alias = '', $params = []) {
    // We check for this specifically because it gets a special alias.
    if ($table == $this->view->storage->get('base_table') && $field == $this->view->storage->get('base_field') && empty($alias)) {
      $alias = $this->view->storage->get('base_field');
    }

    if ($table && empty($this->tableQueue[$table])) {
      $this->ensureTable($table);
    }

    if (!$alias && $table) {
      $alias = $table . '_' . $field;
    }

    // Make sure an alias is assigned.
    $alias = $alias ? $alias : $field;

    // We limit the length of the original alias up to 60 characters
    // to get a unique alias later if its have duplicates.
    $alias = substr($alias, 0, 60);

    // Create a field info array.
    $field_info = [
      'field' => $field,
      'table' => $table,
      'alias' => $alias,
    ] + $params;

    // Test to see if the field is actually the same or not. Due to
    // differing parameters changing the aggregation function, we need
    // to do some automatic alias collision detection:
    $base = $alias;
    $counter = 0;
    while (!empty($this->fields[$alias]) && $this->fields[$alias] != $field_info) {
      $field_info['alias'] = $alias = $base . '_' . ++$counter;
    }

    if (empty($this->fields[$alias])) {
      $this->fields[$alias] = $field_info;
    }

    // Keep track of all aliases used.
    $this->fieldAliases[$table][$field] = $alias;

    return $alias;
  }

  /**
   * Add a filter string to the query.
   *
   * @param string $group
   *   The filter group to add these to; groups are used to create AND/OR
   *   sections of the Google Analytics query. Groups cannot be nested.
   *   Use 0 as the default group.  If the group does not yet exist it will
   *   be created as an AND group.
   * @param string $field
   *   The name of the metric/dimension/field to check.
   * @param mixed $value
   *   The value to test the field against. In most cases, this is a scalar.
   * @param string $operator
   *   The comparison operator, such as =, <, or >=.
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }

    $this->where[$group]['conditions'][] = [
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    ];
  }

  /**
   * Add SORT attribute to the query.
   *
   * @param string $table
   *   NULL, don't use this.
   * @param string $field
   *   The metric/dimensions/field.
   * @param string $order
   *   Either '' for ascending or '-' for descending.
   * @param string $alias
   *   Don't use this yet (at all?).
   * @param array $params
   *   Don't use this yet (at all?).
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = []) {
    $this->orderby[] = [
      'field' => $field,
      'direction' => (strtoupper($order) == 'DESC') ? '-' : '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query($get_count = FALSE) {
    $available_fields = google_analytics_reports_get_fields();
    $query = [];
    foreach ($this->fields as $field) {
      $field_name = google_analytics_reports_variable_to_custom_field($field['field']);
      if ($available_fields[$field_name]) {
        $type = $available_fields[$field_name]->type;
        $type = ($type == 'dimension') ? 'dimensions' : 'metrics';
        $query[$type][] = 'ga:' . $field['field'];
      }
    }

    $filters = [];

    if (isset($this->where)) {
      foreach ($this->where as $where_group => $where) {
        foreach ($where['conditions'] as $condition) {
          $field_name = google_analytics_reports_variable_to_custom_field($condition['field']);

          if ($field_name == '.start_date' || $field_name == '.end_date' || $field_name == 'profile_id') {
            // Remove dot from begging of the string.
            $field_name = ltrim($field_name, '.');
            $query[$field_name] = intval($condition['value']);
          }
          elseif (!empty($available_fields[$field_name])) {
            $filters[$where_group][] = 'ga:' . $condition['field'] . $condition['operator'] . $condition['value'];
          }
        }
        if (!empty($filters[$where_group])) {
          $glue = ($where['type'] == 'AND') ? ';' : ',';
          $filters[$where_group] = implode($glue, $filters[$where_group]);
        }
      }
    }

    if (!empty($filters)) {
      $glue = ($this->groupOperator == 'AND') ? ';' : ',';
      $query['filters'] = implode($glue, $filters);
    }

    if (isset($this->orderby)) {
      foreach ($this->orderby as $field) {
        $query['sort_metric'][] = $field['direction'] . 'ga:' . $field['field'];
      }
    }

    // Change reports profile.
    if (isset($this->options['reports_profile']) && (!empty($this->options['profile_id']))) {
      $query['profile_id'] = $this->options['profile_id'];
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ViewExecutable $view) {
    $this->moduleHandler->invokeAll('views_query_alter', [$view, $this]);
  }

  /**
   * Builds the necessary info to execute the query.
   */
  public function build(ViewExecutable $view) {
    // Store the view in the object to be able to use it later.
    $this->view = $view;

    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();

    $view->build_info['query'] = $this->query();
    $view->build_info['count_query'] = $this->query(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    // Initial check to see if we should attempt to run the query.
    if (!$this->configFactory->get('google_analytics_reports_api.settings')->get('access_token')) {
      // Optionally do not warn users on every query attempt before auth.
      drupal_set_message(t('You must <a href=":url">authorize your site</a> to use your Google Analytics account before you can view reports.', [':url' => Url::fromRoute('google_analytics_reports_api.settings')->toString()]));
      return;
    }

    $query = $view->build_info['query'];
    $count_query = $view->build_info['count_query'];
    $start = microtime(TRUE);

    // Query for total number of items.
    $count_query['max_results'] = 9999;
    $count_query['start_index'] = 1;
    $count_feed = google_analytics_reports_api_report_data($count_query);

    // Process only if data is available.
    if (!empty($count_feed->results->rows)) {
      $view->pager->total_items = count($count_feed->results->rows);
      $view->pager->updatePageInfo();

      // Adjust based on the pager's modifications to limit and offset.
      if (!empty($this->limit) || !empty($this->offset)) {
        $query['max_results'] = intval(!empty($this->limit) ? $this->limit : 1000);
        $query['start_index'] = intval(!empty($this->offset) ? $this->offset : 0) + 1;
      }

      $feed = google_analytics_reports_api_report_data($query);

      $rows = $feed->results->rows;

      $views_result = [];
      $count = 0;
      foreach ($rows as $row) {
        $row['index'] = $count;
        $views_result[] = new ResultRow($row);
        $count++;
      }

      $view->result = isset($views_result) ? $views_result : [];
      $view->execute_time = microtime(TRUE) - $start;
      if ($view->pager->usePager()) {
        $view->total_rows = $view->pager->getTotalItems();
      }

      // Add to build_info['query'] to render query in Views UI query summary
      // area.
      $view->build_info['query'] = print_r($feed->results->query, TRUE);
    }
    else {
      // Set empty query instead of current query array to prevent error
      // in Views UI.
      $view->build_info['query'] = '';
      // Display the error from Google.
      if (!empty($count_feed->response->data)) {
        $response_data = json_decode($count_feed->response->data);
        if (isset($response_data['error']['message'])) {
          drupal_set_message(Html::escape($response_data['error']['message']), 'error');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    // Load profiles list.
    $profile_list = google_analytics_reports_api_profiles_list();

    if ($profile_list) {
      $options['reports_profile'] = [
        'default' => FALSE,
        'translatable' => FALSE,
        'bool' => TRUE,
      ];
      $options['profile_id'] = [
        'default' => $profile_list['profile_id'],
      ];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Load profiles list.
    $profile_list = google_analytics_reports_api_profiles_list();

    $profile_info = '';
    if (isset($profile_list['current_profile'])) {
      $profile_info = parse_url($profile_list['current_profile']->websiteUrl, PHP_URL_HOST) . ' - ' . $profile_list['current_profile']->name . ' (' . $profile_list['current_profile']->id . ')';
    }

    if ($profile_list) {
      $form['reports_profile'] = [
        '#title' => t('Use another reports profile'),
        '#description' => t('This view will use another reports profile rather than system default profile: %profile.', [
          '%profile' => $profile_info,
        ]),
        '#type' => 'checkbox',
        '#default_value' => !empty($this->options['reports_profile']),
      ];
      $form['profile_id'] = [
        '#type' => 'select',
        '#title' => t('Reports profile'),
        '#options' => $profile_list['options'],
        '#description' => t('Choose your Google Analytics profile.'),
        '#default_value' => $this->options['profile_id'],
        '#dependency' => ['edit-query-options-reports-profile' => '1'],
      ];
    }

  }


  /**
   * Make sure table exists.
   *
   * @param string $table
   *   Table name.
   * @param string $relationship
   *   Relationship.
   * @param string $join
   *   Join.
   */
  public function ensureTable($table, $relationship = NULL, $join = NULL) {
  }

}
