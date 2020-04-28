<?php

namespace Drupal\google_analytics_reports\Plugin\views\filter;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * Simple filter to handle numerics for Google Analytics.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("google_analytics_numeric")
 */
class GoogleAnalyticsNumeric extends GoogleAnalyticsBase {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['value'] = [
      'contains' => [
        'min' => ['default' => ''],
        'max' => ['default' => ''],
        'value' => ['default' => ''],
      ],
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = [
      '<' => [
        'title' => t('Is less than'),
        'method' => 'opSimple',
        'short' => t('<'),
        'values' => 1,
      ],
      '<=' => [
        'title' => t('Is less than or equal to'),
        'method' => 'opSimple',
        'short' => t('<='),
        'values' => 1,
      ],
      '==' => [
        'title' => t('Is equal to'),
        'method' => 'opSimple',
        'short' => t('=='),
        'values' => 1,
      ],
      '!=' => [
        'title' => t('Is not equal to'),
        'method' => 'opSimple',
        'short' => t('!='),
        'values' => 1,
      ],
      '>=' => [
        'title' => t('Is greater than or equal to'),
        'method' => 'opSimple',
        'short' => t('>='),
        'values' => 1,
      ],
      '>' => [
        'title' => t('Is greater than'),
        'method' => 'opSimple',
        'short' => t('>'),
        'values' => 1,
      ],
    ];
    return $operators;
  }

  /**
   * Provide a simple textfield for equality.
   *
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    $values = $form_state->getValues();

    $form['value']['#tree'] = TRUE;

    // We have to make some choices when creating this as an exposed
    // filter form. For example, if the operator is locked and thus
    // not rendered, we can't render dependencies; instead we only
    // render the form items we need.
    $which = 'all';
    if (!empty($form['operator'])) {
      $source = ($form['operator']['#type'] == 'radios') ? 'radio:options[operator]' : 'edit-options-operator';
    }

    if (!empty($values['exposed'])) {
      $identifier = $this->options['expose']['identifier'];

      if (empty($this->options['expose']['use_operator']) || empty($this->options['expose']['operator_id'])) {
        // Exposed and locked.
        $which = in_array($this->operator, $this->operatorValues(2)) ? 'minmax' : 'value';
      }
      else {
        $source = 'edit-' . Html::getUniqueId($this->options['expose']['operator_id']);
      }
    }

    if ($which == 'all') {
      $form['value']['value'] = [
        '#type' => 'textfield',
        '#title' => empty($values['exposed']) ? t('Value') : '',
        '#size' => 30,
        '#default_value' => $this->value['value'],
        '#dependency' => [$source => $this->operatorValues(1)],
      ];
      if (!empty($values['exposed']) && !isset($values['input'][$identifier]['value'])) {
        $values['input'][$identifier]['value'] = $this->value['value'];
      }
    }
    elseif ($which == 'value') {
      // When exposed we drop the value-value and just do value if
      // the operator is locked.
      $form['value'] = [
        '#type' => 'textfield',
        '#title' => empty($values['exposed']) ? t('Value') : '',
        '#size' => 30,
        '#default_value' => $this->value['value'],
      ];
      if (!empty($values['exposed']) && !isset($values['input'][$identifier])) {
        $values['input'][$identifier] = $this->value['value'];
      }
    }

    if ($which == 'all' || $which == 'minmax') {
      $form['value']['min'] = [
        '#type' => 'textfield',
        '#title' => empty($values['exposed']) ? t('Min') : '',
        '#size' => 30,
        '#default_value' => $this->value['min'],
      ];
      $form['value']['max'] = [
        '#type' => 'textfield',
        '#title' => empty($values['exposed']) ? t('And max') : t('And'),
        '#size' => 30,
        '#default_value' => $this->value['max'],
      ];
      if ($which == 'all') {
        $dependency = [
          '#dependency' => [$source => $this->operatorValues(2)],
        ];
        $form['value']['min'] += $dependency;
        $form['value']['max'] += $dependency;
      }
      if (!empty($values['exposed']) && !isset($values['input'][$identifier]['min'])) {
        $values['input'][$identifier]['min'] = $this->value['min'];
      }
      if (!empty($values['exposed']) && !isset($values['input'][$identifier]['max'])) {
        $values['input'][$identifier]['max'] = $this->value['max'];
      }

      if (!isset($form['value'])) {
        // Ensure there is something in the 'value'.
        $form['value'] = [
          '#type' => 'value',
          '#value' => NULL,
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value['value'], $this->operator);
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return t('exposed');
    }

    $options = $this->operatorOptions('short');
    $output = Html::escape($options[$this->operator]);
    if (in_array($this->operator, $this->operatorValues(2))) {
      $output .= ' ' . t('@min and @max', ['@min' => $this->value['min'], '@max' => $this->value['max']]);
    }
    elseif (in_array($this->operator, $this->operatorValues(1))) {
      $output .= ' ' . Html::escape($this->value['value']);
    }
    return $output;
  }

  /**
   * Do some minor translation of the exposed input.
   *
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    // Rewrite the input value so that it's in the correct format so that
    // the parent gets the right data.
    if (!empty($this->options['expose']['identifier'])) {
      $value = &$input[$this->options['expose']['identifier']];
      if (!is_array($value)) {
        $value = [
          'value' => $value,
        ];
      }
    }

    $rc = parent::acceptExposedInput($input);

    if (empty($this->options['expose']['required'])) {
      // We have to do some of our own checking for non-required filters.
      $info = $this->operators();
      if (!empty($info[$this->operator]['values'])) {
        switch ($info[$this->operator]['values']) {
          case 1:
            if ($value['value'] === '') {
              return FALSE;
            }
            break;

          case 2:
            if ($value['min'] === '' && $value['max'] === '') {
              return FALSE;
            }
            break;
        }
      }
    }

    return $rc;
  }

  /**
   * Operator values.
   *
   * @param int $values
   *   Value.
   *
   * @return array
   *   Operator keys.
   */
  public function operatorValues($values = 1) {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      if ($info['values'] == $values) {
        $options[] = $id;
      }
    }

    return $options;
  }

}
