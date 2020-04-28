<?php

namespace Drupal\tmgmt_local\Plugin\views\field;

use Drupal\tmgmt_local\LocalTaskInterface;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the link for translating translation task.
 *
 * @ViewsField("tmgmt_local_task_status")
 */
class TaskStatus extends NumericField {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = parent::render($values);
    switch ($value) {
      case LocalTaskInterface::STATUS_PENDING:
        $label = t('Needs action');
        $icon = drupal_get_path('module', 'tmgmt') . '/icons/ready.svg';
        break;

      case LocalTaskInterface::STATUS_COMPLETED:
        $label = t('In review');
        $icon = drupal_get_path('module', 'tmgmt') . '/icons/hourglass.svg';
        break;

      case LocalTaskInterface::STATUS_REJECTED:
        $label = t('Rejected');
        $icon = drupal_get_path('module', 'tmgmt') . '/icons/ex-red.svg';
        break;

      case LocalTaskInterface::STATUS_CLOSED:
        $label = t('Closed');
        $icon = 'core/misc/icons/73b355/check.svg';
        break;

      default:
        $label = t('Unassigned');
        $icon = drupal_get_path('module', 'tmgmt') . '/icons/rejected.svg';
    }
    $element = [
      '#type' => 'inline_template',
      '#template' => '<img src="{{ icon }}" title="{{ label }}"><span></span></img>',
      '#context' => array(
        'icon' => file_create_url($icon),
        'label' => $label,
      ),
    ];
    return \Drupal::service('renderer')->render($element);
  }

}
