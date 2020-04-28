<?php

namespace Drupal\tmgmt_local\Plugin\views\field;

use Drupal\tmgmt_local\LocalTaskItemInterface;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the link for translating translation task items.
 *
 * @ViewsField("tmgmt_local_task_item_status")
 */
class TaskItemStatus extends NumericField {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = parent::render($values);
    switch ($value) {
      case LocalTaskItemInterface::STATUS_PENDING:
        $label = t('Untranslated');
        $icon = drupal_get_path('module', 'tmgmt') . '/icons/ready.svg';
        break;

      case LocalTaskItemInterface::STATUS_COMPLETED:
        $label = t('Translated');
        $icon = drupal_get_path('module', 'tmgmt') . '/icons/gray-check.svg';
        break;

      case LocalTaskItemInterface::STATUS_REJECTED:
        $label = t('Rejected');
        $icon = drupal_get_path('module', 'tmgmt') . '/icons/rejected.svg';
        break;

      case LocalTaskItemInterface::STATUS_CLOSED:
        $label = t('Completed');
        $icon = 'core/misc/icons/73b355/check.svg';
        break;

      default:
        $label = t('Untranslated');
        $icon = drupal_get_path('module', 'tmgmt') . '/icons/ready.svg';
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
