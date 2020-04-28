<?php

namespace Drupal\tmgmt\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the label for a job or job item.
 *
 * @ViewsField("tmgmt_entity_label")
 */
class EntityLabel extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_entity'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_entity'] = [
      '#title' => $this->t('Link to entity'),
      '#description' => $this->t('Make entity label a link to entity page.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_entity']),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    if ($entity = $this->getEntity($values)) {

      if (!empty($this->options['link_to_entity'])) {
        $this->options['alter']['url'] = $entity->toUrl();
        $this->options['alter']['make_link'] = TRUE;
      }

      return $this->sanitizeValue($entity->label());
    }
  }

}
