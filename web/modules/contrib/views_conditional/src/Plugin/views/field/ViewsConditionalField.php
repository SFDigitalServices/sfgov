<?php

/**
 * @file
 * Definition of Drupal\views_conditional\Plugin\views\field\ViewsConditionalField
 */

namespace Drupal\views_conditional\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_conditional_field")
 */
class ViewsConditionalField extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
    $this->field_alias = 'views_conditional_' . $this->position;
  }
  // Conditional operators.
  public $conditions = [
    1 => 'Equal to',
    2 => 'NOT equal to',
    3 => 'Greater than',
    4 => 'Less than',
    5 => 'Empty',
    6 => 'NOT empty',
    7 => 'Contains',
    8 => 'Does NOT contain',
    9 => 'Length Equal to',
    10 => 'Length NOT equal to',
    11 => 'Length Greater than',
    12 => 'Length Less than',
  ];

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['label']['default'] = NULL;

    $options['if'] = ['default' => ''];
    $options['condition'] = ['default' => ''];
    $options['equalto'] = ['default' => ''];
    $options['then'] = ['default' => ''];
    $options['or'] = ['default' => ''];
    $options['strip_tags'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['views_conditional.settings'];
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['relationship']['#access'] = FALSE;
    $previous = $this->getPreviousFieldLabels();
    $fields = ['- ' . $this->t('no field selected') . ' -'];
    foreach ($previous as $id => $label) {
      $field[$id] = $label;
    }
    $fields += $field;

    $form['if'] = [
      '#type' => 'select',
      '#title' => $this->t('If this field...'),
      '#options' => $fields,
      '#default_value' => $this->options['if'],
    ];
    $form['condition'] = [
      '#type' => 'select',
      '#title' => $this->t('Is...'),
      '#options' => $this->conditions,
      '#default_value' => $this->options['condition'],
    ];
    $form['equalto'] = [
      '#type' => 'textfield',
      '#title' => $this->t('This value'),
      '#description' => $this->t('Input a value to compare the field against.  Replacement variables may be used'),
      '#default_value' => $this->options['equalto'],
    ];
    $form['then'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Then output this...'),
      '#description' => $this->t('Input what should be output.  Replacement variables may be used.'),
      '#default_value' => $this->options['then'],
    ];
    $form['or'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Otherwise, output this...'),
      '#description' => $this->t('Input what should be output if the above conditions do NOT evaluate to true.'),
      '#default_value' => $this->options['or'],
    ];
    $form['strip_tags'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip html tags from the output'),
      '#default_value' => $this->options['strip_tags'],
    ];
    $form['replacements'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#title' => $this->t('Replacement Variables'),
    ];
    $form['replacements']['notice'] = [
      '#markup' => 'You may use any of these replacement variables in the "equals" or the "output" text fields.  If you wish to use brackets ([ or ]), replace them with %5D or %5E.',
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $items = [
      'DATE_UNIX => Current date / time, in UNIX timestamp format (' . REQUEST_TIME . ')',
      'DATE_STAMP => Current date / time, in standard format (' . format_date(REQUEST_TIME) . ')',
    ];
    $views_fields = $this->view->display_handler->getHandlers('field');
    foreach ($views_fields as $field => $handler) {
      // We only use fields up to (not including) this one.
      if ($field == $this->options['id']) {
        break;
      }
      $items[] = "{{ $field }}";
    }
    $form['replacements']['variables'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (empty($values['options']['if']) || empty($values['options']['condition']) || empty($values['options']['equalto'])) {
      if (empty($values['options']['if'])) {
        $form_state->setErrorByName('if', $this->t("Please specify a valid field to run a condition on."));
      }
      if (empty($values['options']['condition'])) {
        $form_state->setErrorByName('condition', t("Please select a conditional operator."));
      }
      // We using there is_numeric because values '0', '0.0' counting as empty in PHP language.
      if (empty($values['options']['equalto']) && !in_array($values['options']['condition'], [
          5,
          6
        ]) && !is_numeric($values['options']['equalto'])
      ) {
        $form_state->setErrorByName('condition', t("Please specify something to compare with."));
      }
    }
  }

  /**
   * Cleans a variable for handling later.
   */
  public function clean_var($var) {
    $unparsed = isset($var->last_render) ? $var->last_render : '';
    return $this->options['strip_tags'] ? trim(strip_tags($unparsed)) : trim($unparsed);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $if = $this->options['if'];
    $condition = $this->options['condition'];
    $equalto = $this->options['equalto'];
    $then = $this->options['then'];
    $or = ($this->options['or'] ? $this->options['or'] : '');

    // Gather field information.
    $fields = $this->view->display_handler->getHandlers('field');
    $labels = $this->view->display_handler->getFieldLabels();
    // Search through field information for possible replacement variables.
    foreach ( $labels as $key => $var) {
      // If we find a replacement variable, replace it.
      if (strpos($equalto, "{{ $key }}") !== FALSE) {
        $field = $this->clean_var($fields[$key]);
        $equalto = $this->t(str_replace("{{ $key }}", $field, $equalto));
      }
      if (strpos($then, "{{ $key }}") !== FALSE) {
        $field = $this->clean_var($fields[$key]);
        $then = $this->t(str_replace("{{ $key }}", $field, $then));
      }
      if (strpos($or, "{{ $key }}") !== FALSE) {
        $field = $this->clean_var($fields[$key]);
        $or = $this->t(str_replace("{{ $key }}", $field, $or));
      }
    }

    // If we find a date stamp replacement, replace that.
    if (strpos($equalto, 'DATE_STAMP') !== FALSE) {
      $equalto = str_replace('DATE_STAMP', format_date(REQUEST_TIME), $equalto);
    }
    if (strpos($then, 'DATE_STAMP') !== FALSE) {
      $then = str_replace('DATE_STAMP', format_date(REQUEST_TIME), $then);
    }
    if (strpos($or, 'DATE_STAMP') !== FALSE) {
      $or = str_replace('DATE_STAMP', format_date(REQUEST_TIME), $or);
    }

    // If we find a unix date stamp replacement, replace that.
    if (strpos($equalto, 'DATE_UNIX') !== FALSE) {
      $equalto = str_replace('DATE_UNIX', REQUEST_TIME, $equalto);
    }
    if (strpos($then, 'DATE_UNIX') !== FALSE) {
      $then = str_replace('DATE_UNIX', REQUEST_TIME, $then);
    }
    if (strpos($or, 'DATE_UNIX') !== FALSE) {
      $or = str_replace('DATE_UNIX', REQUEST_TIME, $or);
    }

    // Strip tags on the "if" field.  Otherwise it appears to
    // output as <div class="xxx">Field data</div>...
    // ...which of course makes it difficult to compare.
    $r = isset($fields["$if"]->last_render) ? trim(strip_tags($fields["$if"]->last_render, '<img>')) : NULL;

    // Run conditions.
    switch ($condition) {
      // Equal to.
      case 1:
        if ($r == $equalto) {
          return $then;
        }
        else {
          return $or;
        }
        break;

      // Not equal to.
      case 2:
        if ($r !== $equalto) {
          return $then;
        }
        else {
          return $or;
        }
        break;

      // Greater than.
      case 3:
        if ($r > $equalto) {
          return $then;
        }
        else {
          return $or;
        }
        break;

      // Less than.
      case 4:
        if ($r < $equalto) {
          return $then;
        }
        else {
          return $or;
        }
        break;

      // Empty.
      case 5:
        if (empty($r)) {
          return $then;
        }
        else {
          return $or;
        }
        break;

      // Not empty.
      case 6:
        if (!empty($r)) {
          return $then;
        }
        else {
          return $or;
        }
        break;

      // Contains
      case 7:
        if (mb_stripos($r, $equalto) !== FALSE) {
          return $then;
        }
        else {
          return $or;
        }
        break;

      // Does NOT contain
      case 8:
        if (mb_stripos($r, $equalto) === FALSE) {
          return $then;
        }
        else {
          return $or;
        }
        break;

      // Length Equal to.
      case 9:
        if (mb_strlen($r) == $equalto) {
          return $then;
        }
        else {
          return $or;
        }
        break;

      // Length Not equal to.
      case 10:
        if (mb_strlen($r) !== $equalto) {
          return $then;
        }
        else {
          return $or;
        }
        break;

      // Length Greater than.
      case 11:
        if (mb_strlen($r) > $equalto) {
          return $then;
        }
        else {
          return $or;
        }
        break;

      // Length Less than.
      case 12:
        if (mb_strlen($r) < $equalto) {
          return $then;
        }
        else {
          return $or;
        }
        break;
    }
  }
}
